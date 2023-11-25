<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\eca\Plugin\Action\ListAddBase;

/**
 * Action to add a specified entity to a list.
 *
 * @Action(
 *   id = "eca_list_add_entity",
 *   label = @Translation("List: add entity"),
 *   description = @Translation("Add a specified entity to a list."),
 *   type = "entity"
 * )
 */
class ListAddEntity extends ListAddBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->addItem($entity);
  }

}
