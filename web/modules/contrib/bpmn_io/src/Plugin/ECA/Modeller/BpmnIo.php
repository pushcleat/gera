<?php

namespace Drupal\bpmn_io\Plugin\ECA\Modeller;

use Drupal\Core\Url;
use Drupal\eca_modeller_bpmn\ModellerBpmnBase;

/**
 * Plugin implementation of the ECA Modeller.
 *
 * @EcaModeller(
 *   id = "bpmn_io",
 *   label = "BPMN.iO",
 *   description = "BPMN modeller with a feature-rich UI."
 * )
 */
class BpmnIo extends ModellerBpmnBase {

  /**
   * {@inheritdoc}
   */
  protected function xmlNsPrefix(): string {
    return 'bpmn2:';
  }

  /**
   * {@inheritdoc}
   */
  public function isEditable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function edit(): array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'bpmn-io',
      ],
      'canvas' => [
        '#prefix' => '<div class="canvas"></div><div class="property-panel"></div>',
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'actions',
        ],
        'save' => [
          '#type' => 'button',
          '#value' => $this->t('Save'),
          '#attributes' => [
            'class' => ['button--primary eca-save'],
          ],
        ],
        'close' => [
          '#type' => 'button',
          '#value' => $this->t('Close'),
          '#attributes' => [
            'class' => ['eca-close'],
          ],
        ],
        'tb' => $this->tokenBrowserService->getTokenBrowserMarkup(),
      ],
      '#attached' => [
        'library' => [
          'bpmn_io/ui',
        ],
        'drupalSettings' => [
          'bpmn_io' => [
            'id' => $this->eca->id(),
            'isnew' => $this->eca->isNew(),
            'modeller' => 'bpmn_io',
            'bpmn' => $this->eca->getModel()->getModeldata(),
            'templates' => $this->getTemplates(),
            'save_url' => Url::fromRoute('eca.save', ['modeller_id' => 'bpmn_io'])->toString(),
            'collection_url' => Url::fromRoute('entity.eca.collection')->toString(),
          ],
        ],
      ],
    ];
  }

}
