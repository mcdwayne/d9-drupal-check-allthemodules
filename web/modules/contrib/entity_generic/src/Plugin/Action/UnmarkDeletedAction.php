<?php

namespace Drupal\entity_generic\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_generic\Entity\GenericInterface;

/**
 * @Action(
 *   id = "entity_generic:unmark_deleted_action",
 *   label = @Translation("Unmark entity as deleted"),
 *   deriver = "Drupal\entity_generic\Plugin\Action\Derivative\UnmarkDeletedActionDeriver",
 * )
 */
class UnmarkDeletedAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->setDeleted(GenericInterface::ENTITY_GENERIC_UNDELETED);
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
