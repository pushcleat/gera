<?php

namespace Drupal\eca_ui\DataCollector;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webprofiler\DataCollector\DataCollectorTrait;
use Drupal\webprofiler\DataCollector\HasPanelInterface;
use Drupal\webprofiler\DataCollector\PanelTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Provides EcaDataCollector for the webprofiler of devel.
 */
class EcaDataCollector10 extends DataCollector implements HasPanelInterface {

  use StringTranslationTrait, DataCollectorTrait, EcaDataCollectorTrait, PanelTrait;

  /**
   * {@inheritdoc}
   */
  public function collect(Request $request, Response $response, \Throwable $exception = null): void {
    $this->doCollect();
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->data = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPanel(): array {
    if (empty($this->data)) {
      return [
        '#markup' => $this->t('No debugging data available.')
      ];
    }
    $rows = [];
    foreach ($this->data as $data) {
      $rows[] = [
        [
          'data' => $data['message'],
          'class' => 'webprofiler__key',
        ],
        [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{{ data|raw }}',
            '#context' => [
              'data' => $data['tokens'],
            ],
          ],
          'class' => 'webprofiler__value',
        ],
      ];
    }

    return [
      'Debugging' => [
        '#theme' => 'webprofiler_dashboard_section',
        '#title' => 'Debugging',
        '#data' => [
          '#type' => 'table',
          '#header' => [$this->t('Step'), $this->t('Tokens')],
          '#rows' => $rows,
          '#attributes' => [
            'class' => [
              'webprofiler__table',
            ],
          ],
        ],
      ],
    ];
  }

}
