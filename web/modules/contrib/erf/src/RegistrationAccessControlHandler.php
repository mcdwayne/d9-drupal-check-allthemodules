<?php

namespace Drupal\erf;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Registration entity.
 *
 * @see \Drupal\erf\Entity\Registration.
 */
class RegistrationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\erf\Entity\RegistrationInterface $entity */
    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        if ($account->id() == $entity->getOwnerId()) {
          return AccessResult::allowedIfHasPermission($account, 'manage own registrations');
        }
        return AccessResult::allowedIfHasPermission($account, 'administer registrations');
      }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add registrations');
  }

}
