<?php

namespace Drupal\entity_generic\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_generic\Entity\GenericInterface;

/**
 * @Action(
 *   id = "entity_generic:mark_deleted_action",
 *   label = @Translation("Mark entity as deleted"),
 *   deriver = "Drupal\entity_generic\Plugin\Action\Derivative\MarkDeletedActionDeriver",
 * )
 */
class MarkDeletedAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->setDeleted(GenericInterface::ENTITY_GENERIC_DELETED);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
