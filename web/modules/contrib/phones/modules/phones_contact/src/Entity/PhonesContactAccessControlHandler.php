<?php

namespace Drupal\phones_contact\Entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Phones contact entity.
 *
 * @see \Drupal\phones_contact\Entity\PhonesContact.
 */
class PhonesContactAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\phones_contact\Entity\PhonesContactInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished phones contact entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published phones contact entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit phones contact entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete phones contact entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add phones contact entities');
  }

}
