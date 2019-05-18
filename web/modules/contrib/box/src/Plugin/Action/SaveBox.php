<?php

namespace Drupal\box\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an action that can save any entity.
 *
 * @Action(
 *   id = "box_save_action",
 *   label = @Translation("Save box"),
 *   type = "box"
 * )
 */
class SaveBox extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\box\Entity\BoxInterface $entity */
    // We need to change at least one value, otherwise the changed timestamp
    // will not be updated.
    $entity->changed = 0;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\box\Entity\BoxInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

}
