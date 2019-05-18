<?php

namespace Drupal\bom;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the bom_component entity type.
 *
 * @see \Drupal\bom\Entity\Component
 */
class ComponentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\bom\BomInterface $bom */
    if ($bom = $entity->bom->entity) {
      return $bom->access($operation, $account, $return_as_object);
    }

    $result = parent::access($entity, $operation, $account, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

}
