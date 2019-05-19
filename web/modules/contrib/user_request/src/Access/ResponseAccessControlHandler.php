<?php

namespace Drupal\user_request\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\UncacheableEntityAccessControlHandler;

/**
 * Checks permission to execute operations on response entities.
 */
class ResponseAccessControlHandler extends UncacheableEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Only performs additional checking if result returned from parent is
    // neutral.
    $account = $this->prepareUser($account);
    $result = parent::checkAccess($entity, $operation, $account);
    if ($result->isNeutral()) {
      // Checks operation on received responses.
      $result = $this->checkReceivedPermissions($entity, $operation, $account);
    }
    return $result;
  }

  /**
   * Checks if an account has permission to perform some operation on a
   * received response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkReceivedPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    // Gets the response's request.
    $request = $entity->getRequest();

    // Checks if user is the owner of the request.
    if ($request->getOwnerId() == $account->id()) {
      // Checks permissions.
      $entity_type_id = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $permissions = [
        "$operation received $entity_type_id",
        "$operation received $bundle $entity_type_id",
      ];
      $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }

    return isset($result) ? $result : AccessResult::neutral();
  }

}
