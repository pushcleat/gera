<?php

namespace Drupal\eca_endpoint\Plugin\Action;

/**
 * Get the requested uri.
 *
 * @Action(
 *   id = "eca_endpoint_get_request_uri",
 *   label = @Translation("Request: Get uri")
 * )
 */
class GetRequestUri extends RequestActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getRequestValue() {
    return $this->getRequest()->getRequestUri();
  }

}
