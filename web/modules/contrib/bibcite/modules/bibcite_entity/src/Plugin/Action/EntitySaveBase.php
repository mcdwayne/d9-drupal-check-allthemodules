<?php

namespace Drupal\bibcite_entity\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Base entity save action.
 */
class EntitySaveBase extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\bibcite_entity\Entity\ReferenceInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity->changed = 0;
    $entity->save();
  }

}
