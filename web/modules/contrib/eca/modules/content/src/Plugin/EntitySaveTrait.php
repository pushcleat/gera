<?php

namespace Drupal\eca_content\Plugin;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;

/**
 * Trait for saving an entity within ECA operations.
 */
trait EntitySaveTrait {

  /**
   * Saves an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   *
   * @return int|null
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *   Returns NULL when no saving was performed at all.
   *
  *  @throws \Drupal\Core\Entity\EntityStorageException
  *    In case of failures an exception is thrown.
   */
  protected function saveEntity(EntityInterface $entity): ?int {
    // For nested updates, the original object may need a refresh.
    // @see https://www.drupal.org/project/eca/issues/3331810
    if (isset($entity->original) && !$entity->isNew()) {
      // Behave the same as \Drupal\Core\Entity\EntityStorageBase::doPreSave.
      $id = $entity->getOriginalId() ?? $entity->id();
      if ($id !== NULL) {
        $etm = $this->entityTypeManager ?? \Drupal::entityTypeManager();
        try {
          $entity->original = $etm->getStorage($entity->getEntityTypeId())
            ->loadUnchanged($id);
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
          // This exception can not happen because the plugin is definitly
          // available, otherwise the $entity would be available either.
        }
      }
    }

    return $entity->save();
  }

}
