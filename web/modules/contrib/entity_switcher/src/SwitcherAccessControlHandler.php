<?php

namespace Drupal\entity_switcher;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the switcher settings configuration
 * entity type.
 *
 * @see \Drupal\entity_switcher\Entity\Switcher
 */
class SwitcherAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('administer entity switchers') ||
      $account->hasPermission('access entity switchers')) {
      $result = AccessResult::allowed()->cachePerPermissions();
    }
    else {
      $result = AccessResult::forbidden()->cachePerPermissions();
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('administer entity switchers')) {
      $result = AccessResult::allowed()->cachePerPermissions();
    }
    else {
      $result = AccessResult::forbidden()->cachePerPermissions();
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

}
