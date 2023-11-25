<?php

namespace Drupal\eca_misc\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Facade of the Symfony request event, represented as Token data provider.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class RequestEventFacade extends Event implements DataProviderInterface {

  /**
   * The request event.
   *
   * @var \Symfony\Component\HttpKernel\Event\RequestEvent
   */
  protected RequestEvent $event;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructs a new RequestEventFacade object.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to decorate.
   */
  public function __construct(RequestEvent $event) {
    $this->event = $event;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key) {
    if ($key === 'event') {
      if (!isset($this->eventData)) {
        $this->eventData = DataTransferObject::create([
          'machine-name' => RequestEvent::class,
          'method' => $this->event->getRequest()->getMethod(),
          'path' => $this->event->getRequest()->getPathInfo(),
        ]);
      }

      return $this->eventData;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData(string $key): bool {
    return $this->getData($key) !== NULL;
  }

}
