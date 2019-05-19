<?php

namespace Drupal\user_request\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\UncacheableEntityAccessControlHandler;

/**
 * Checks permission to execute operations on request entities.
 */
class RequestAccessControlHandler extends UncacheableEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Only performs additional checking if result returned from parent is
    // neutral.
    $account = $this->prepareUser($account);
    $result = parent::checkAccess($entity, $operation, $account);
    if ($result->isNeutral()) {
      // Checks permission for received requests.
      $result = $this->checkRecipientPermissions($entity, $operation, $account);
    }
    return $result;
  }

  /**
   * Checks if an account has permission to perform some operation on a
   * received request.
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
  protected function checkRecipientPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    // Checks if account belongs to recipient.
    $is_recipient = FALSE;
    $recipients  = $entity->getRecipients();
    foreach ($recipients as $recipient) {
      if ($recipient->id() == $account->id()) {
        $is_recipient = TRUE;
        break;
      }
    }

    // Checks if has permission to perform operation on received requests.
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    if ($is_recipient) {
      $permissions = [
        "$operation received $entity_type_id",
        "$operation received $bundle $entity_type_id",
      ];
      if ($operation == 'update') {
        // Update is also allowed if the user has permission to respond.
        $permissions[] = "respond $entity_type_id";
        $permissions[] = "respond $bundle $entity_type_id";
      }
      $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }

    return isset($result) ? $result : AccessResult::neutral();
  }

}
