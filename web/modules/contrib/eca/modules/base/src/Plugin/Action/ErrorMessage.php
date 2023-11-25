<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\MessageAction;

/**
 * Sends a error message to the current user's screen.
 *
 * @Action(
 *   id = "eca_error_message",
 *   label = @Translation("Display an error message to the user"),
 *   type = "system"
 * )
 */
class ErrorMessage extends MessageAction {

  /**
   * {@inheritdoc}
   *
   * Mainly copied from parent execute method, except for a different messenger
   * instruction.
   */
  public function execute($entity = NULL) {
    if (empty($this->configuration['node'])) {
      $this->configuration['node'] = $entity;
    }
    $message = $this->token->replace($this->configuration['message'], $this->configuration);
    $build = [
      '#markup' => $message,
    ];

    // @todo Fix in https://www.drupal.org/node/2577827
    $this->messenger->addError($this->renderer->renderPlain($build));
  }

}
