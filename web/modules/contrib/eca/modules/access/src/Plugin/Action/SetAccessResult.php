<?php

namespace Drupal\eca_access\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Event\AccessEventInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;

/**
 * Action to set an access result.
 *
 * @Action(
 *   id = "eca_access_set_result",
 *   label = @Translation("Set access result"),
 *   description = @Translation("Only works when reacting upon <em>ECA Access</em> events.")
 * )
 */
class SetAccessResult extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $this->event instanceof AccessEventInterface ? AccessResult::allowed() : AccessResult::forbidden("Event is not compatible with this action.");
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if (!($this->event instanceof AccessEventInterface)) {
      return;
    }

    switch ($this->configuration['access_result']) {

      case 'allowed':
        // Allowed by configured ECA.
        $result = AccessResult::allowed();
        break;

      case 'neutral':
        $result = AccessResult::neutral("Neutral by configured ECA");
        break;

      default:
        $result = AccessResult::forbidden("Forbidden by configured ECA");

    }

    $this->event->setAccessResult($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'access_result' => 'forbidden',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['access_result'] = [
      '#type' => 'select',
      '#title' => $this->t('Access result'),
      '#description' => $this->t('Please note: This action only works when reacting upon <em>access</em> events.'),
      '#default_value' => $this->configuration['access_result'],
      '#options' => [
        'forbidden' => $this->t('Forbidden'),
        'neutral' => $this->t('Neutral (no opinion)'),
        'allowed' => $this->t('Allowed'),
      ],
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['access_result'] = $form_state->getValue('access_result');
    parent::submitConfigurationForm($form, $form_state);
  }

}
