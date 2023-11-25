<?php

namespace Drupal\eca\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Objects\EcaEvent;

/**
 * Dispatches after initial successor execution of an ECA configuration.
 */
class AfterInitialExecutionEvent extends Event {

  /**
   * The ECA configuration.
   *
   * @var \Drupal\eca\Entity\Eca
   */
  protected Eca $eca;

  /**
   * The ECA event object.
   *
   * @var \Drupal\eca\Entity\Objects\EcaEvent
   */
  protected EcaEvent $ecaEvent;

  /**
   * The applied system event.
   *
   * @var \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event
   */
  protected object $event;

  /**
   * The name of the applying system event.
   *
   * @var string
   */
  protected string $eventName;

  /**
   * Array holding arbitrary variables the represent a pre-execution state.
   *
   * These values come from an according BeforeInitialExecutionEvent.
   *
   * @var array
   */
  protected array $prestate = [];

  /**
   * The AfterInitialExecutionEvent constructor.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   *   The ECA configuration.
   * @param \Drupal\eca\Entity\Objects\EcaEvent $ecaEvent
   *   The ECA event object.
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The applied system event.
   * @param string $event_name
   *   The name of the applying system event.
   * @param array &$prestate
   *   Array holding arbitrary variables of a prestate (if any).
   */
  public function __construct(Eca $eca, EcaEvent $ecaEvent, object $event, string $event_name, array &$prestate) {
    $this->eca = $eca;
    $this->ecaEvent = $ecaEvent;
    $this->event = $event;
    $this->eventName = $event_name;
    $this->prestate = &$prestate;
  }

  /**
   * Get the ECA configuration.
   *
   * @return \Drupal\eca\Entity\Eca
   *   The ECA configuration.
   */
  public function getEca(): Eca {
    return $this->eca;
  }

  /**
   * Get the ECA event object.
   *
   * @return \Drupal\eca\Entity\Objects\EcaEvent
   *   The ECA event object.
   */
  public function getEcaEvent(): EcaEvent {
    return $this->ecaEvent;
  }

  /**
   * Get the applied system event.
   *
   * @return \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event
   *   The applied system event.
   */
  public function getEvent(): object {
    return $this->event;
  }

  /**
   * Get the name of the applying system event.
   *
   * @return string
   *   The name of the applying system event.
   */
  public function getEventName(): string {
    return $this->eventName;
  }

  /**
   * Get the value of a prestate variable.
   *
   * @param string|null $name
   *   The name of the variable. Set to NULL to return the whole array.
   *
   * @return mixed
   *   The value. Returns NULL if not present.
   */
  public function &getPrestate(?string $name) {
    if (!isset($name)) {
      return $this->prestate;
    }

    $value = NULL;
    if (isset($this->prestate[$name])) {
      $value = &$this->prestate[$name];
    }
    return $value;
  }

}
