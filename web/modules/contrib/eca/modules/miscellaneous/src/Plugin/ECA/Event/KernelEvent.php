<?php

namespace Drupal\eca_misc\Plugin\ECA\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Plugin implementation of the ECA Events for the kernel.
 *
 * @EcaEvent(
 *   id = "kernel",
 *   deriver = "Drupal\eca_misc\Plugin\ECA\Event\KernelEventDeriver"
 * )
 */
class KernelEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'view' => [
        'label' => 'Controller does not return a Response instance',
        'event_name' => KernelEvents::VIEW,
        'event_class' => ViewEvent::class,
        'description' => new TranslatableMarkup('Fires, when a controller does not return a Response instance.'),
      ],
      'controller' => [
        'label' => 'Controller found to handle request',
        'event_name' => KernelEvents::CONTROLLER,
        'event_class' => ControllerEvent::class,
        'description' => new TranslatableMarkup('Fires, once a controller was found for handling a request.'),
      ],
      'controller_arguments' => [
        'label' => 'Controller arguments have been resolved',
        'event_name' => KernelEvents::CONTROLLER_ARGUMENTS,
        'event_class' => ControllerArgumentsEvent::class,
        'description' => new TranslatableMarkup('Fires, once controller arguments have been resolved.'),
      ],
      'exception' => [
        'label' => 'Uncaught exception',
        'event_name' => KernelEvents::EXCEPTION,
        'event_class' => ExceptionEvent::class,
        'description' => new TranslatableMarkup('Fires, when an uncaught exception appears.'),
      ],
      'finish_request' => [
        'label' => 'Response for request created',
        'event_name' => KernelEvents::FINISH_REQUEST,
        'event_class' => FinishRequestEvent::class,
        'description' => new TranslatableMarkup('Fires, when a response was generated for a request.'),
      ],
      'request' => [
        'label' => 'Start dispatching request',
        'event_name' => KernelEvents::REQUEST,
        'event_class' => RequestEvent::class,
        'description' => new TranslatableMarkup('Fires at the very beginning of request dispatching.'),
      ],
      'response' => [
        'label' => 'Response created',
        'event_name' => KernelEvents::RESPONSE,
        'event_class' => ResponseEvent::class,
        'description' => new TranslatableMarkup('Fires, once a response was created for replying to a request.'),
      ],
      'terminate' => [
        'label' => 'Response was sent',
        'event_name' => KernelEvents::TERMINATE,
        'event_class' => TerminateEvent::class,
        'description' => new TranslatableMarkup('Fires, once a response was sent.'),
      ],
      'container_initialize_subrequest_finished' => [
        'label' => 'Service container finished initializing',
        'event_name' => DrupalKernelInterface::CONTAINER_INITIALIZE_SUBREQUEST_FINISHED,
        'event_class' => Event::class,
        'description' => new TranslatableMarkup('Fires, when the service container finished initializing in subrequest.'),
      ],
    ];
  }

}
