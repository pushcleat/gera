<?php

namespace Drupal\eca_queue\Plugin\QueueWorker;

use Drupal\eca_queue\QueueEvents;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\eca\Token\TokenInterface;
use Drupal\eca_queue\Event\ProcessingTaskEvent;
use Drupal\eca_queue\Exception\NotYetDueForProcessingException;
use Drupal\eca_queue\Task;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Processes enqueued ECA tasks.
 *
 * @QueueWorker(
 *   id = "eca_task",
 *   title = @Translation("ECA Tasks"),
 *   cron = {"time" = 15},
 *   deriver = "Drupal\eca_queue\Plugin\QueueWorker\TaskWorkerDeriver"
 * )
 */
class TaskWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * The Token services.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenServices;

  /**
   * Constructs a TaskWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\eca\Token\TokenInterface $token_services
   *   The Token services.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, TokenInterface $token_services) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventDispatcher = $event_dispatcher;
    $this->tokenServices = $token_services;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('eca.token_services')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!($data instanceof Task)) {
      return;
    }
    $task = $data;
    if (!$task->isDueForProcessing()) {
      throw new NotYetDueForProcessingException('Task is not yet due for processing.');
    }
    $this->tokenServices->addTokenDataProvider($task);
    $this->eventDispatcher->dispatch(new ProcessingTaskEvent($task), QueueEvents::PROCESSING_TASK);
    $this->tokenServices->removeTokenDataProvider($task);
  }

  /**
   * Normalizes the user-defined task name to be compatible with machine names.
   *
   * @param string $task_name
   *   The task name to normalize.
   *
   * @return string
   *   The normalized task name.
   */
  public static function normalizeTaskName(string $task_name): string {
    return str_replace(' ', '_', mb_strtolower(trim($task_name)));
  }

}
