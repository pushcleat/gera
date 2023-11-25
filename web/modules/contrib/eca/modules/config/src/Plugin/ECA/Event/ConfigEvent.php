<?php

namespace Drupal\eca_config\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\Importer\MissingContentEvent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Event\Tag;

/**
 * Plugin implementation of the ECA Events for config.
 *
 * @EcaEvent(
 *   id = "config",
 *   deriver = "Drupal\eca_config\Plugin\ECA\Event\ConfigEventDeriver"
 * )
 */
class ConfigEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $actions = [
      'delete' => [
        'label' => 'Delete config',
        'event_name' => ConfigEvents::DELETE,
        'event_class' => ConfigCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'collection_info' => [
        'label' => 'Collect information on all config collections',
        'event_name' => ConfigEvents::COLLECTION_INFO,
        'event_class' => ConfigCollectionInfo::class,
        'tags' => Tag::READ | Tag::PERSISTENT | Tag::AFTER,
      ],
      'import' => [
        'label' => 'Import config',
        'event_name' => ConfigEvents::IMPORT,
        'event_class' => ConfigImporterEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'import_missing_content' => [
        'label' => 'Import config but content missing',
        'event_name' => ConfigEvents::IMPORT_MISSING_CONTENT,
        'event_class' => MissingContentEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'import_validate' => [
        'label' => 'Import config validation',
        'event_name' => ConfigEvents::IMPORT_VALIDATE,
        'event_class' => ConfigImporterEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'rename' => [
        'label' => 'Rename config',
        'event_name' => ConfigEvents::RENAME,
        'event_class' => ConfigRenameEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'save' => [
        'label' => 'Save config',
        'event_name' => ConfigEvents::SAVE,
        'event_class' => ConfigCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    if ($this->eventClass() === ConfigCrudEvent::class) {
      $form['help'] = [
        '#type' => 'markup',
        '#markup' => $this->t('This event provides two tokens: <em>"[config:*]"</em> to access properties of a configuration, and <em>"[config_name]"</em> to get the machine name of a configuration (e.g. "system.site").'),
        '#weight' => 10,
        '#description' => $this->t('This event provides two tokens: <em>"[config:*]"</em> to access properties of a configuration, and <em>"[config_name]"</em> to get the machine name of a configuration (e.g. "system.site").'),
      ];
    }
    return $form;
  }

}
