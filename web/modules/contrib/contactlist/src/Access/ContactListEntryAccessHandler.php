<?php

namespace Drupal\contactlist\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the contact list entry entity type.
 */
class ContactListEntryAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\contactlist\Entity\ContactListEntryInterface $entity */
    if ($account->id() == $entity->getOwner()->id()) {
      return AccessResult::allowedIfHasPermission($account, $operation . ' contact list entry');
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
