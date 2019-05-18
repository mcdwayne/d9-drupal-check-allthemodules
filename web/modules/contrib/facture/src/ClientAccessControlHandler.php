<?php

namespace Drupal\facture;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Client entity.
 *
 * @see \Drupal\facture\Entity\Client.
 */
class ClientAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\facture\Entity\ClientInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished client entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published client entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit client entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete client entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add client entities');
  }

}
