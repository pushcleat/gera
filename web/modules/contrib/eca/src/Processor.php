<?php

namespace Drupal\eca;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Objects\EcaObject;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\AfterInitialExecutionEvent;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\Plugin\CleanupInterface;
use Drupal\eca\Plugin\ObjectWithPluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event as ContractsEvent;

/**
 * Executes enabled ECA config regards applying events.
 */
class Processor {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * A shortened list of ECA events where execution was applied.
   *
   * This list is only used as a temporary reminder for being able to recognize
   * a possible infinite recursion.
   *
   * @var array
   */
  protected array $executionHistory = [];

  /**
   * A parameterized threshold of the maximum allowed level of recursion.
   *
   * @var int
   */
  protected int $recursionThreshold;

  /**
   * A flag indicating whether an error was already logged regards recursion.
   *
   * The flag is used to prevent log flooding, as this may quickly happen when
   * infinite recursion would happen a lot. The site owner should see at least
   * one of such an error and may (hopefully) react accordingly.
   *
   * @var bool
   */
  protected bool $recursionErrorLogged = FALSE;

  /**
   * Processor constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param int $recursion_threshold
   *   A parameterized threshold of the maximum allowed level of recursion.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, EventDispatcherInterface $event_dispatcher, int $recursion_threshold) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
    $this->recursionThreshold = $recursion_threshold;
  }

  /**
   * Main method that executes ECA config regards applying events.
   *
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The event being triggered.
   * @param string $event_name
   *   The event name that was triggered.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \InvalidArgumentException
   *   When the given event is not of a documented object type.
   */
  public function execute(object $event, string $event_name): void {
    if (!($event instanceof Event) && !($event instanceof ContractsEvent)) {
      throw new \InvalidArgumentException(sprintf('Passed $event parameter is not of an expected event object type, %s given', get_class($event)));
    }
    $context = [
      '%event' => get_class($event),
    ];
    /** @var \Drupal\eca\Entity\EcaStorage $eca_storage */
    $eca_storage = $this->entityTypeManager->getStorage('eca');
    foreach ($eca_storage->loadByEvent($event, $event_name) as $eca) {
      unset($context['%eventlabel'], $context['%eventid']);
      $context['%ecalabel'] = $eca->label();
      $context['%ecaid'] = $eca->id();
      foreach ($eca->getUsedEvents() as $ecaEvent) {
        $context['%eventlabel'] = $ecaEvent->getLabel();
        $context['%eventid'] = $ecaEvent->getId();
        $this->logger->debug('Check %eventlabel (%eventid) from ECA %ecalabel (%ecaid) for event %event.', $context);
        if ($ecaEvent->applies($event, $event_name)) {
          // We need to check whether this is the root of all execution calls,
          // for being able to purge the whole execution history once it is not
          // needed anymore.
          $is_root_execution = empty($this->executionHistory);
          // Take a look for a repetitive execution order. If we find one,
          // we see it as the beginning of infinite recursion and stop.
          if (!$is_root_execution && $this->recursionThresholdSurpassed($ecaEvent)) {
            if (!$this->recursionErrorLogged) {
              $this->logger->error('Recursion within configured ECA events detected. Please adjust your ECA configuration so that it avoids infinite loops. Affected event: %eventlabel (%eventid) from ECA %ecalabel (%ecaid).', $context);
              $this->recursionErrorLogged = TRUE;
            }
            continue;
          }

          // Temporarily keep in mind on which ECA event object execution is
          // about to be applied. If that behavior starts to repeat, then halt
          // the execution pipeline to prevent infinite recursion.
          $this->executionHistory[] = $ecaEvent;

          $before_event = new BeforeInitialExecutionEvent($eca, $ecaEvent, $event, $event_name);
          $this->eventDispatcher->dispatch($before_event, EcaEvents::BEFORE_INITIAL_EXECUTION);

          // Now that we have any required context, we may execute the logic.
          $this->logger->info('Start %eventlabel (%eventid) from ECA %ecalabel (%ecaid) for event %event.', $context);
          $this->executeSuccessors($eca, $ecaEvent, $event, $context);
          // At this point, no nested triggering of events happened or was
          // prevented by something else. Therefore remove the last added
          // item from the history stack as it's not needed anymore.
          array_pop($this->executionHistory);

          $this->eventDispatcher->dispatch(new AfterInitialExecutionEvent($eca, $ecaEvent, $event, $event_name, $before_event->getPrestate(NULL)), EcaEvents::AFTER_INITIAL_EXECUTION);

          if ($is_root_execution) {
            // Forget what we've done here. We only take care for nested
            // triggering of events regarding possible infinite recursion.
            // By resetting the array, all root-level executions will not know
            // anything from each other.
            $this->executionHistory = [];
          }
        }
      }
    }
  }

  /**
   * Executes the successors.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   *   The ECA config entity.
   * @param \Drupal\eca\Entity\Objects\EcaObject $eca_object
   *   The ECA item that was just executed and looks for its successors.
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The event that was originally triggered.
   * @param array $context
   *   List of key value pairs, used to generate meaningful log messages.
   */
  protected function executeSuccessors(Eca $eca, EcaObject $eca_object, object $event, array $context): void {
    $executedSuccessorIds = [];
    foreach ($eca->getSuccessors($eca_object, $event, $context) as $successor) {
      $context['%actionlabel'] = $successor->getLabel();
      $context['%actionid'] = $successor->getId();
      if (in_array($successor->getId(), $executedSuccessorIds, TRUE)) {
        $this->logger->debug('Prevent duplicate execution of %actionlabel (%actionid) from ECA %ecalabel (%ecaid) for event %event.', $context);
        continue;
      }
      $this->logger->info('Execute %actionlabel (%actionid) from ECA %ecalabel (%ecaid) for event %event.', $context);
      if ($successor->execute($eca_object, $event, $context)) {
        $executedSuccessorIds[] = $successor->getId();
        $this->executeSuccessors($eca, $successor, $event, $context);
      }
    }
    if ($eca_object instanceof ObjectWithPluginInterface) {
      $plugin = $eca_object->getPlugin();
      if ($plugin instanceof CleanupInterface) {
        $plugin->cleanupAfterSuccessors();
      }
    }
  }

  /**
   * Checks the ECA event object whether it surpasses the recursion threshold.
   *
   * @param \Drupal\eca\Entity\Objects\EcaEvent $ecaEvent
   *   The ECA event object to check for.
   *
   * @return bool
   *   Returns TRUE when recursion threshold was surpassed, FALSE otherwise.
   */
  protected function recursionThresholdSurpassed(EcaEvent $ecaEvent): bool {
    if (!in_array($ecaEvent, $this->executionHistory, TRUE)) {
      return FALSE;
    }
    $block_size = -1;
    $recursion_level = 1;
    $executed_block = [];
    $entry = end($this->executionHistory);
    while ($entry) {
      array_unshift($executed_block, $entry);
      if ($entry === $ecaEvent) {
        $block_size = count($executed_block);
        break;
      }
      $entry = prev($this->executionHistory);
    }
    while (!($recursion_level > $this->recursionThreshold)) {
      $entry = end($executed_block);
      $block_index = 0;
      while ($entry) {
        if ($entry !== prev($this->executionHistory)) {
          break 2;
        }
        $block_index++;
        if ($block_index >= $block_size) {
          $recursion_level++;
          break;
        }
        $entry = prev($executed_block);
      }
    }
    return $recursion_level > $this->recursionThreshold;
  }

}
