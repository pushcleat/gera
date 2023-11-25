<?php

namespace Drupal\eca_endpoint\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines ECA endpoint routes.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class EndpointRoutes implements ContainerInjectionInterface {

  /**
   * The ECA endpoint path.
   *
   * @var string
   */
  protected string $endpointBasePath;

  /**
   * Instantiates a EndpointRoutes object.
   *
   * @param string $endpoint_base_path
   *   The ECA endpoint base path.
   */
  public function __construct(string $endpoint_base_path) {
    $this->endpointBasePath = $endpoint_base_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->getParameter('eca_endpoint.base_path')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = new RouteCollection();

    $routes->add('eca_endpoint.endpoint', new Route(
      '/' . $this->endpointBasePath . '/{eca_endpoint_argument_1}/{eca_endpoint_argument_2}',
      ['_controller' => 'Drupal\eca_endpoint\Controller\EndpointController::handle'],
      ['_custom_access' => 'Drupal\eca_endpoint\Controller\EndpointController::access']
    ));

    return $routes;
  }

}
