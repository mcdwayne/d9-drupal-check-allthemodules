<?php

namespace Drupal\private_page\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the private_page entity.
 */
class PrivatePageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit private page');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete private page');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add private page');
  }

}
