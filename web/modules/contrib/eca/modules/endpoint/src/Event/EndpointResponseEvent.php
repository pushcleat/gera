<?php

namespace Drupal\eca_endpoint\Event;

use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Event\RenderEventInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;
use Drupal\eca_endpoint\EndpointEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dispatched when an ECA Endpoint response is being rendered.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_endpoint\Event
 */
class EndpointResponseEvent extends EndpointEventBase implements DataProviderInterface, RenderEventInterface {

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  public Request $request;

  /**
   * The response.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  public Response $response;

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public AccountInterface $account;

  /**
   * The render array build.
   *
   * @var array
   */
  public array $build;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructs a new EcaRenderEndpointResponseEvent object.
   *
   * @param array &$path_arguments
   *   The arguments provided in the URL path.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param array &$build
   *   The render array build.
   */
  public function __construct(array &$path_arguments, Request $request, Response $response, AccountInterface $account, array &$build) {
    $this->pathArguments = &$path_arguments;
    $this->request = $request;
    $this->response = $response;
    $this->account = $account;
    $this->build = &$build;
  }

  /**
   * {@inheritdoc}
   */
  public function &getRenderArray(): array {
    return $this->build;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData(string $key): bool {
    return $this->getData($key) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key) {
    if ($key === 'event') {
      if (!isset($this->eventData)) {
        $this->eventData = DataTransferObject::create([
          'machine-name' => EndpointEvents::RESPONSE,
          'path-arguments' => $this->pathArguments,
          'request' => [
            'method' => $this->request->getMethod(),
            'path' => $this->request->getPathInfo(),
            'query' => $this->request->query->all(),
            'headers' => $this->request->headers->all(),
            'content-type' => method_exists($this->request, 'getContentTypeFormat') ? $this->request->getContentTypeFormat() : $this->request->getContentType(),
            'content' => (string) $this->request->getContent(),
            'ip' => $this->request->getClientIp(),
          ],
          'uid' => $this->account->id(),
        ]);
      }

      return $this->eventData;
    }

    return NULL;
  }

}
