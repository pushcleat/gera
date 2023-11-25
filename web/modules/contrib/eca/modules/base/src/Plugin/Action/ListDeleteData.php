<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\eca\Plugin\Action\ListDataOperationBase;

/**
 * Action to perform a delete transaction on a list.
 *
 * @Action(
 *   id = "eca_list_delete_data",
 *   label = @Translation("List: delete data"),
 *   description = @Translation("Transaction to delete contained data of a list from the database.")
 * )
 */
class ListDeleteData extends ListDataOperationBase {

  /**
   * {@inheritdoc}
   */
  protected static string $operation = 'delete';

}
