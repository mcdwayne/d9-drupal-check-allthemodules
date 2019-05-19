<?php

namespace Drupal\zendesk_tickets;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines the access control handler for the form type entity type.
 *
 * @see \Drupal\zendesk_tickets\Entity\ZendeskTicketFormType
 */
class ZendeskTicketFormTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);

    // DENY if the parent denies.
    if ($access->isForbidden()) {
      return $access;
    }

    if (in_array($operation, ['submit', 'enable', 'disable'], TRUE)) {
      $admin_permission = $this->entityType->getAdminPermission();

      if ($operation == 'submit') {
        // Submit operation.
        // If no admin permission or user is not an admin ...
        if (!$admin_permission || !$account->hasPermission($admin_permission)) {
          // Control submit access via the status.
          if (!$entity->canSubmit()) {
            // DENY submit if entity is disabled or the form is disabled.
            // This ensure that the Zendesk form controls access and that
            // Drupal admins can only disable forms actively visible in Zendesk.
            $access = AccessResult::forbidden();
          }
          else {
            // ALLOW submit if have permission.
            $access = AccessResult::allowedIfHasPermission($account, "submit zendesk ticket forms");
          }
        }
      }
      elseif ($operation == 'enable') {
        // Enable operation.
        $access = AccessResult::allowedIf((!$entity->status() && $entity->ticketFormStatus()));
      }
      elseif ($operation == 'disable') {
        // Disable operation.
        $access = AccessResult::allowedIf($entity->status());
      }
    }

    return $access;
  }

}
