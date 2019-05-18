<?php

namespace Drupal\annotations;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the annotation entity.
 *
 * @see \Drupal\annotations\Entity\Annotations.
 */
class AnnotationsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view annotations entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit annotations entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete annotations entity');
      case 'forbidden':
        return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add annotations entity');
  }

}
