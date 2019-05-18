<?php

namespace Drupal\mailing_list;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller for the mailing list entity.
 *
 * @see \Drupal\mailing_list\Entity\MailingList
 */
class MailingListAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer mailing lists')) {
      return AccessResult::allowed();
    }

    $list_id = $entity->id();

    if ($operation == 'view' || $operation == 'view label') {
      // Allowed for users with this entity related permission.
      $allowed_permissions = [
        'administer mailing list subscriptions',
        "subscribe to $list_id mailing list",
        "view any $list_id mailing list subscriptions",
        "update any $list_id mailing list subscriptions",
      ];

      foreach ($allowed_permissions as $permission) {
        if ($account->hasPermission($permission)) {
          return AccessResult::allowed();
        }
      }
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
