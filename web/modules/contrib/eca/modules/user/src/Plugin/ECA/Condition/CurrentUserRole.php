<?php

namespace Drupal\eca_user\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Plugin implementation of the ECA condition of the current user's role.
 *
 * @EcaCondition(
 *   id = "eca_current_user_role",
 *   label = @Translation("Role of current user"),
 *   description = @Translation("Checks, whether the current user has a given role.")
 * )
 */
class CurrentUserRole extends BaseUser {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $userRoles = $this->currentUser->getRoles();
    $result = in_array($this->configuration['role'], $userRoles, TRUE);
    return $this->negationCheck($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'role' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $roles = [];
    /** @var \Drupal\user\RoleInterface $role */
    foreach (Role::loadMultiple() as $role) {
      $roles[$role->id()] = $role->label();
    }
    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('User role'),
      '#description' => $this->t('The user role to check, like <em>editor</em> or <em>administrator</em>.'),
      '#default_value' => $this->configuration['role'],
      '#options' => $roles,
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['role'] = $form_state->getValue('role');
    parent::submitConfigurationForm($form, $form_state);
  }

}
