<?php

namespace Drupal\rng_contact\AccessControl;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for rng_contact entity.
 */
class RngContactAccessControl extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\rng_contact\Entity\RngContactInterface $entity */
    $account = $this->prepareUser($account);
    $type = $entity->bundle();

    if (in_array($operation, ['view', 'update', 'delete'])) {
      if ($account->hasPermission("$operation any $type rng_contact")) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      if ($owner = $entity->getOwner()) {
        if (($account->id() === $owner->id()) && $account->hasPermission("$operation own $type rng_contact")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if (isset($entity_bundle)) {
      if ($account->hasPermission("create $entity_bundle rng_contact")) {
        $result = AccessResult::allowed()->cachePerPermissions();
        return $return_as_object ? $result : $result->isAllowed();
      }
    }

    $result = parent::createAccess($entity_bundle, $account, $context, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
