<?php

namespace Drupal\eca\Entity\Objects;

use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Plugin\ECA\Event\EventInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Plugin\ObjectWithPluginInterface;
use Drupal\eca_content\Event\ContentEntityBaseEntity;

/**
 * Provides an ECA item of type event for internal processing.
 */
class EcaEvent extends EcaObject implements ObjectWithPluginInterface {

  /**
   * ECA event plugin.
   *
   * @var \Drupal\eca\Plugin\ECA\Event\EventInterface
   */
  protected EventInterface $plugin;

  /**
   * Event constructor.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   *   The ECA config entity.
   * @param string $id
   *   The event ID provided by the modeller.
   * @param string $label
   *   The event label.
   * @param \Drupal\eca\Plugin\ECA\Event\EventInterface $plugin
   *   The event plugin.
   */
  public function __construct(Eca $eca, string $id, string $label, EventInterface $plugin) {
    parent::__construct($eca, $id, $label, $this);
    $this->plugin = $plugin;
  }

  /**
   * Determines if the event should be executed.
   *
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The event being triggered.
   * @param string $event_name
   *   The event name being triggered.
   *
   * @return bool
   *   TRUE, if this event should be executed in the current context, FALSE
   *   otherwise.
   */
  public function applies(object $event, string $event_name): bool {
    if ($event_name === $this->plugin->eventName() && (!($event instanceof ConditionalApplianceInterface) || $event->applies($this->getId(), $this->configuration))) {
      if ($event instanceof ContentEntityBaseEntity) {
        return !empty($event->getEntity());
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the plugin instance.
   *
   * @return \Drupal\eca\Plugin\ECA\Event\EventInterface
   *   The plugin instance.
   */
  public function getPlugin(): EventInterface {
    return $this->plugin;
  }

}
