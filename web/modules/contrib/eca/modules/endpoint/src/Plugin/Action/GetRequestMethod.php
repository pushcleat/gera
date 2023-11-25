<?php

namespace Drupal\eca_endpoint\Plugin\Action;

/**
 * Get the request method.
 *
 * @Action(
 *   id = "eca_endpoint_get_request_method",
 *   label = @Translation("Request: Get method")
 * )
 */
class GetRequestMethod extends RequestActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getRequestValue() {
    return $this->getRequest()->getMethod();
  }

}
