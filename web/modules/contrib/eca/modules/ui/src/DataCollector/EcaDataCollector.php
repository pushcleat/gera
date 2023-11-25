<?php

namespace Drupal\eca_ui\DataCollector;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webprofiler\DataCollector\DrupalDataCollectorTrait;
use Drupal\webprofiler\DrupalDataCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Provides EcaDataCollector for the webprofiler of devel.
 */
class EcaDataCollector extends DataCollector implements DrupalDataCollectorInterface {

  use StringTranslationTrait, DrupalDataCollectorTrait, EcaDataCollectorTrait;

  /**
   * {@inheritdoc}
   */
  public function collect(Request $request, Response $response): void {
    $this->doCollect();
  }

}
