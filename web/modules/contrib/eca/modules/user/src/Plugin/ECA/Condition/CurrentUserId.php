<?php

namespace Drupal\eca_user\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the ECA condition of the current user's id.
 *
 * @EcaCondition(
 *   id = "eca_current_user_id",
 *   label = @Translation("Current user ID"),
 *   description = @Translation("Compares a user ID with the current user ID.")
 * )
 */
class CurrentUserId extends BaseUser {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    // We need to cast the ID of the current user as we sometimes receive a
    // string instead of an integer despite the fact that the interface at
    // \Drupal\Core\Session\AccountInterface::id describes that an integer
    // should be returned.
    // We need to cast the ID to string to avoid false positives when an
    // empty string value get compared to integer 0.
    $result = (string) $this->tokenServices->replace($this->configuration['user_id']) === (string) $this->currentUser->id();
    return $this->negationCheck($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'user_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#description' => $this->t('The user ID, which gets compared. This value supports tokens.'),
      '#default_value' => $this->configuration['user_id'],
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['user_id'] = $form_state->getValue('user_id');
    parent::submitConfigurationForm($form, $form_state);
  }

}
