<?php

namespace Drupal\wisski_core\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the comment entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class WisskiBundleAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
  /*
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view contact entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit contact entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete contact entity');
    }
  */
//  dpm(func_get_args(),__METHOD__);
    return AccessResult::allowedIfHasPermission($account,'administer wisski_core bundles');
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
//    dpm(func_get_args(),__METHOD__);
    return AccessResult::allowedIfHasPermission($account, 'administer wisski_core bundles');
  }

}
