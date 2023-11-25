<?php

namespace Drupal\eca_endpoint\Plugin\Action;

/**
 * Get the request content.
 *
 * @Action(
 *   id = "eca_endpoint_get_request_content",
 *   label = @Translation("Request: Get content")
 * )
 */
class GetRequestContent extends RequestActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getRequestValue() {
    return (string) $this->getRequest()->getContent();
  }

}
