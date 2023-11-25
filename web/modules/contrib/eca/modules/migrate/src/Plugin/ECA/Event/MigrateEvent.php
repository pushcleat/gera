<?php

namespace Drupal\eca_migrate\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateIdMapMessageEvent;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateMapDeleteEvent;
use Drupal\migrate\Event\MigrateMapSaveEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Event\MigrateRowDeleteEvent;

/**
 * Plugin implementation of the ECA Events for migrate.
 *
 * @EcaEvent(
 *   id = "migrate",
 *   deriver = "Drupal\eca_migrate\Plugin\ECA\Event\MigrateEventDeriver"
 * )
 */
class MigrateEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $actions = [];
    $actions['idmap_message'] = [
      'label' => 'Save message to ID map',
      'event_name' => MigrateEvents::IDMAP_MESSAGE,
      'event_class' => MigrateIdMapMessageEvent::class,
    ];
    $actions['map_delete'] = [
      'label' => 'Remove entry from migration map',
      'event_name' => MigrateEvents::MAP_DELETE,
      'event_class' => MigrateMapDeleteEvent::class,
    ];
    $actions['map_save'] = [
      'label' => 'Save to migration map',
      'event_name' => MigrateEvents::MAP_SAVE,
      'event_class' => MigrateMapSaveEvent::class,
    ];
    $actions['post_import'] = [
      'label' => 'Migration import finished',
      'event_name' => MigrateEvents::POST_IMPORT,
      'event_class' => MigrateImportEvent::class,
    ];
    $actions['post_rollback'] = [
      'label' => 'Migration rollback finished',
      'event_name' => MigrateEvents::POST_ROLLBACK,
      'event_class' => MigrateRollbackEvent::class,
    ];
    $actions['post_row_delete'] = [
      'label' => 'Migration row deleted',
      'event_name' => MigrateEvents::POST_ROW_DELETE,
      'event_class' => MigrateRowDeleteEvent::class,
    ];
    $actions['post_row_save'] = [
      'label' => 'Migration row saved',
      'event_name' => MigrateEvents::POST_ROW_SAVE,
      'event_class' => MigratePostRowSaveEvent::class,
    ];
    $actions['pre_import'] = [
      'label' => 'Migration import started',
      'event_name' => MigrateEvents::PRE_IMPORT,
      'event_class' => MigrateImportEvent::class,
    ];
    $actions['pre_rollback'] = [
      'label' => 'Migration rollback started',
      'event_name' => MigrateEvents::PRE_ROLLBACK,
      'event_class' => MigrateRollbackEvent::class,
    ];
    $actions['pre_row_delete'] = [
      'label' => 'Deleting migration row',
      'event_name' => MigrateEvents::PRE_ROW_DELETE,
      'event_class' => MigrateRowDeleteEvent::class,
    ];
    $actions['pre_row_save'] = [
      'label' => 'Saving migration row',
      'event_name' => MigrateEvents::PRE_ROW_SAVE,
      'event_class' => MigratePreRowSaveEvent::class,
    ];
    return $actions;
  }

}
