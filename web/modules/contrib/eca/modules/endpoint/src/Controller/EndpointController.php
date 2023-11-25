<?php

namespace Drupal\eca_endpoint\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\MainContent\HtmlRenderer;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Event\AccessEventInterface;
use Drupal\eca\Event\RenderEventInterface;
use Drupal\eca\Event\TriggerEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The ECA endpoint controller.
 */
class EndpointController implements ContainerInjectionInterface {

  /**
   * The trigger event service.
   *
   * @var \Drupal\eca\Event\TriggerEvent
   */
  protected TriggerEvent $triggerEvent;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The main content renderer.
   *
   * @var \Drupal\Core\Render\MainContent\HtmlRenderer
   */
  protected HtmlRenderer $mainContentHtmlRenderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): EndpointController {
    return new static(
      $container->get('eca.trigger_event'),
      $container->get('renderer'),
      $container->get('main_content_renderer.html'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a new EcaEndpointController object.
   *
   * @param \Drupal\eca\Event\TriggerEvent $trigger_event
   *   The trigger event service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Render\MainContent\HtmlRenderer $html_renderer
   *   The main content renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(TriggerEvent $trigger_event, RendererInterface $renderer, HtmlRenderer $html_renderer, RouteMatchInterface $route_match, AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->triggerEvent = $trigger_event;
    $this->renderer = $renderer;
    $this->mainContentHtmlRenderer = $html_renderer;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * Handles the request to the endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account.
   * @param string|null $eca_endpoint_argument_1
   *   (optional) An additional path argument.
   * @param string|null $eca_endpoint_argument_2
   *   (optional) An additional path argument.
   */
  public function handle(Request $request, ?AccountInterface $account = NULL, ?string $eca_endpoint_argument_1 = NULL, ?string $eca_endpoint_argument_2 = NULL) {
    $account = $account ?? $this->currentUser;

    $path_arguments = [];
    if (isset($eca_endpoint_argument_1)) {
      $path_arguments[] = $eca_endpoint_argument_1;
    }
    if (isset($eca_endpoint_argument_2)) {
      $path_arguments[] = $eca_endpoint_argument_2;
    }

    $neutral = AccessResult::neutral("No ECA configuration set an access result");
    $event = $this->triggerEvent->dispatchFromPlugin('eca_endpoint:access', $path_arguments, $account, $neutral);
    if (!($event instanceof AccessEventInterface) || !($result = $event->getAccessResult())) {
      $result = $neutral;
    }
    if ($result->isForbidden()) {
      // Access has been explicitly revoked. Therefore, return a 403.
      throw new AccessDeniedHttpException();
    }
    if (!$result->isAllowed()) {
      // No explicit access is allowed. Therefore, return a 404.
      // This may happen on following situations:
      // - No ECA configuration reacts upon the endpoint with given arguments
      //   at all, or
      // - An ECA configuration does react upon this for creating a response,
      //   but there is no ECA configuration that defines access for it.
      if (RfcLogLevel::DEBUG === (int) $this->configFactory->get('eca.settings')->get('log_level')) {
        \Drupal::logger('eca')->debug("Returning a 404 page, because no access has been explicitly set for either revoking or granting access. Request path: %request_url", [
          '%request_path' => $request->getPathInfo(),
        ]);
      }
      throw new NotFoundHttpException();
    }

    $build = [];
    $response = new Response();
    // Make the response uncacheable by default.
    $response->setPrivate();

    // Keep in mind the current headers and content, to check if it got changed.
    $previous_headers = $response->headers->all();
    $previous_content = $response->getContent();

    $event = $this->triggerEvent->dispatchFromPlugin('eca_endpoint:response', $path_arguments, $request, $response, $account, $build);
    if ($event instanceof RenderEventInterface) {
      $build = &$event->getRenderArray();
    }

    if (($response->headers->all() === $previous_headers) && ($response->getContent() === $previous_content)) {
      // No headers have been set, and no response content has been set.
      // Return the render array build as page content, if it was set.
      if ($build) {
        return $build;
      }
    }
    else {
      // The response got set, therefore it will be returned.
      if (!$response->headers->has('Content-Type')) {
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
      }
      [$content_type] = explode(';', $response->headers->get('Content-Type'), 2);
      $content_type = trim((string) ($content_type ?: 'text/html'));
      $is_html_response = mb_strpos($content_type, 'html') !== FALSE;

      if ($build && !$response->getContent()) {
        // A render build is given, and response content has not been directly
        // set. For this case, render the render array build, and use serialized
        // contents if suitable.
        if ($is_html_response) {
          $content_response = $this->mainContentHtmlRenderer->renderResponse($build, $request, $this->routeMatch);
          // Merge in custom headers, then return it.
          foreach ($response->headers->all() as $k => $v) {
            $content_response->headers->set($k, $v);
          }
          return $content_response;
        }

        $serialized_contents = [];
        $only_serialized_contents = TRUE;
        if (!Element::children($build)) {
          $build = [$build];
        }
        foreach ($build as &$v) {
          if (isset($v['#serialized']) && !Element::children($v)) {
            $serialized_contents[] = $v['#serialized'];
            $v['#wrap'] = FALSE;
          }
          else {
            $only_serialized_contents = FALSE;
          }
        }
        unset($v);
        if ($only_serialized_contents) {
          $content = implode("\n", $serialized_contents);
        }
        else {
          $content = $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$build) {
            return $this->renderer->render($build);
          });
        }

        $response->setContent($content);

        // Adjust max-age caching if necessary.
        $metadata = BubbleableMetadata::createFromRenderArray($build);
        if (isset($build['#cache']['max-age'])) {
          if ($response->getMaxAge() !== NULL) {
            $metadata->mergeCacheMaxAge($response->getMaxAge());
          }
          if (!$metadata->getCacheMaxAge()) {
            $response->setPrivate();
            $response->setMaxAge(0);
            $response->setSharedMaxAge(0);
            $response->setExpires((new DrupalDateTime("@0"))->getPhpDateTime());
          }
          elseif ($metadata->getCacheMaxAge() < $response->getMaxAge()) {
            $response->setMaxAge($metadata->getCacheMaxAge());
            $response->setSharedMaxAge($metadata->getCacheMaxAge());
          }
        }
      }

      return $response;
    }

    // No response content has been set via ECA. Therefore, return a 404.
    throw new NotFoundHttpException();
  }

  /**
   * Access check for the endpoint.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The current user account.
   * @param string|null $eca_endpoint_argument_1
   *   (optional) An additional path argument.
   * @param string|null $eca_endpoint_argument_2
   *   (optional) An additional path argument.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(?AccountInterface $account = NULL, ?string $eca_endpoint_argument_1 = NULL, ?string $eca_endpoint_argument_2 = NULL): AccessResultInterface {
    // Local menu links are being built up using a "fake" route match. Therefore
    // we catch the current route match from the global container instead.
    $current_route_match = \Drupal::routeMatch();
    $route = $current_route_match->getRouteObject();
    if ($route && ($route->getDefault('_controller') === 'Drupal\eca_endpoint\Controller\EndpointController::handle')) {
      // Let ::handle decide whether access is allowed.
      return AccessResult::allowed()
        ->addCacheContexts([
          'url.path',
          'url.query_args',
          'user',
          'user.permissions',
        ]);
    }

    $account = $account ?? $this->currentUser;
    $path_arguments = [];
    if (isset($eca_endpoint_argument_1)) {
      $path_arguments[] = $eca_endpoint_argument_1;
    }
    if (isset($eca_endpoint_argument_2)) {
      $path_arguments[] = $eca_endpoint_argument_2;
    }

    $forbidden = AccessResult::forbidden("No ECA configuration set an access result");
    $event = $this->triggerEvent->dispatchFromPlugin('eca_endpoint:access', $path_arguments, $account, $forbidden);
    if ($event instanceof AccessEventInterface && ($result = $event->getAccessResult())) {
      return $result;
    }
    return $forbidden;
  }

}
