<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\eca\EcaEvents;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\Event\RenderEventInterface;

/**
 * Adds sensible cache contexts and tags to render arrays of render events.
 */
class EcaExecutionRenderSubscriber extends EcaBase {

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    if ($event instanceof RenderEventInterface) {
      $render_array = &$event->getRenderArray();
      $metadata = BubbleableMetadata::createFromRenderArray($render_array);
      // Vary by path, query arguments and user account.
      $metadata->addCacheContexts([
        'url.path',
        'url.query_args',
        'user',
        'user.permissions',
      ]);
      // Invalidate when ECA config changes.
      $metadata->addCacheTags(['config:eca_list']);
      $metadata->applyTo($render_array);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = ['onBeforeInitialExecution'];
    return $events;
  }

}
