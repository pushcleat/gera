<?php

namespace Drupal\eca\Commands;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Service\Modellers;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class EcaCommands extends DrushCommands {

  /**
   * ECA config entity storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $configStorage;

  /**
   * EVA modeller service.
   *
   * @var \Drupal\eca\Service\Modellers
   */
  protected Modellers $modellerServices;

  /**
   * EcaCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eca\Service\Modellers $modeller_services
   *   The ECA modeller services.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Modellers $modeller_services) {
    parent::__construct();
    $this->configStorage = $entity_type_manager->getStorage('eca');
    $this->modellerServices = $modeller_services;
  }

  /**
   * Import a single ECA file.
   *
   * @param string $plugin_id
   *   The id of the modeller plugin.
   * @param string $filename
   *   The file name to import, relative to the Drupal root or absolute.
   *
   * @usage eca:import
   *   Import a single ECA file.
   *
   * @command eca:import
   */
  public function import(string $plugin_id, string $filename): void {
    $modeller = $this->modellerServices->getModeller($plugin_id);
    if ($modeller === NULL) {
      $this->io()->error('This modeller plugin does not exist.');
      return;
    }
    if (!file_exists($filename)) {
      $this->io()->error('This file does not exist.');
      return;
    }
    try {
      $modeller->save(file_get_contents($filename), $filename);
    }
    catch (\LogicException | EntityStorageException $e) {
      $this->io()->error($e->getMessage());
    }
  }

  /**
   * Update all previously imported ECA files.
   *
   * @usage eca:reimport
   *   Update all previously imported ECA files.
   *
   * @command eca:reimport
   */
  public function reimportAll(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->modellerServices->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      if ($modeller->isEditable()) {
        // Editable models have no external files.
        continue;
      }
      $model = $eca->getModel();
      $filename = $model->getFilename();
      if (!file_exists($filename)) {
        $this->logger->error('This file ' . $filename . ' does not exist.');
        continue;
      }
      try {
        $modeller->save(file_get_contents($filename), $filename);
      }
      catch (\LogicException | EntityStorageException $e) {
        $this->io()->error($e->getMessage());
      }
    }
  }

  /**
   * Export templates for all ECA modellers.
   *
   * @command eca:export:templates
   */
  public function exportTemplates(): void {
    foreach ($this->modellerServices->getModellerDefinitions() as $plugin_id => $definition) {
      $modeller = $this->modellerServices->getModeller($plugin_id);
      if ($modeller === NULL) {
        $this->io()->error('This modeller plugin does not exist.');
        continue;
      }
      $modeller->exportTemplates();
    }
  }

  /**
   * Updates all existing ECA entities calling ::updateModel in their modeller.
   *
   * It is the modeller's responsibility to load all existing plugins and find
   * out if the model data, which is proprietary to them, needs to be updated.
   *
   * @usage eca:update
   *   Update all models if plugins got changed.
   *
   * @command eca:update
   */
  public function updateAllModels(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->modellerServices->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      $model = $eca->getModel();
      $modeller->setConfigEntity($eca);
      if ($modeller->updateModel($model)) {
        $filename = $model->getFilename();
        if ($filename && file_exists($filename)) {
          file_put_contents($filename, $model->getModeldata());
        }
        try {
          $modeller->save($model->getModeldata(), $filename);
        }
        catch (\LogicException | EntityStorageException $e) {
          $this->io()->error($e->getMessage());
        }
      }
    }
  }

  /**
   * Disable all existing ECA entities.
   *
   * @usage eca:disable
   *   Disable all models.
   *
   * @command eca:disable
   */
  public function disableAllModels(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->modellerServices->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      $modeller
        ->setConfigEntity($eca)
        ->disable();
    }
  }

  /**
   * Enable all existing ECA entities.
   *
   * @usage eca:enable
   *   Enable all models.
   *
   * @command eca:enable
   */
  public function enableAllModels(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->modellerServices->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      $modeller
        ->setConfigEntity($eca)
        ->enable();
    }
  }

}
