<?php

namespace Drupal\eca_endpoint\Plugin\Action;

/**
 * Get the client IP.
 *
 * @Action(
 *   id = "eca_endpoint_get_client_ip",
 *   label = @Translation("Request: Get client IP")
 * )
 */
class GetClientIp extends RequestActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getRequestValue() {
    return $this->getRequest()->getClientIp();
  }

}
