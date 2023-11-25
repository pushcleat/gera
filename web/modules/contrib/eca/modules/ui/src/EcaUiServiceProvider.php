<?php

namespace Drupal\eca_ui;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\eca_ui\DataCollector\EcaDataCollector10;

/**
 * Provider for dynamically provided services by ECA UI.
 */
class EcaUiServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if (interface_exists('Drupal\webprofiler\DataCollector\TemplateAwareDataCollectorInterface')) {
      $definition = $container->getDefinition('webprofiler.eca_ui');
      $definition->setClass(EcaDataCollector10::class);
      $tags = $definition->getTags();
      $tags['data_collector'][0]['template'] = '@eca_ui/Collector/eca10.html.twig';
      $definition->setTags($tags);
    }
  }

}
