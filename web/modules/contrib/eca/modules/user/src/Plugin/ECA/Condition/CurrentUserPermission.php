<?php

namespace Drupal\eca_user\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the ECA condition of the current user's permissions.
 *
 * @EcaCondition(
 *   id = "eca_current_user_permission",
 *   label = @Translation("Current user has permission"),
 *   description = @Translation("Checks, whether the current user has a given permission.")
 * )
 */
class CurrentUserPermission extends BaseUser {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    return $this->negationCheck($this->currentUser->hasPermission($this->configuration['permission']));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'permission' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $permissions = [];
    /** @var \Drupal\user\PermissionHandler $handler */
    $handler = \Drupal::service('user.permissions');
    foreach ($handler->getPermissions() as $permission => $def) {
      $permissions[$permission] = strip_tags((string) $def['title']);
    }
    $form['permission'] = [
      '#type' => 'select',
      '#title' => $this->t('Permission'),
      '#description' => $this->t('The permission to check, like <em>administer node display</em>.'),
      '#default_value' => $this->configuration['permission'],
      '#options' => $permissions,
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['permission'] = $form_state->getValue('permission');
    parent::submitConfigurationForm($form, $form_state);
  }

}
