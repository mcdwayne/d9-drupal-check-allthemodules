<?php

/**
 * @file
 * Contains \Drupal\log\Plugin\Action\MarkAsDone.
 */

namespace Drupal\log\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Marks a log as done.
 *
 * @Action(
 *   id = "log_done_action",
 *   label = @Translation("Mark as done"),
 *   type = "log"
 * )
 */
class MarkAsDone extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->get('done')->setValue(TRUE);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\log\LogInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->get('done')->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
