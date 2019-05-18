<?php
/**
 * @file
 * Contains \Drupal\collect\Model\ModelAccessControlHandler.
 */

namespace Drupal\collect\Model;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls access to models.
 */
class ModelAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\collect\Model\ModelInterface $entity */
    return parent::checkAccess($entity, $operation, $account)
      // Deny delete access to locked configs.
      ->andIf(AccessResult::allowedIf($operation != 'delete' || !$entity->isLocked()));
  }

}
