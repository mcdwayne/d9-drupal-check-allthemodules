<?php

namespace Drupal\zchat;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Zchat Message entity.
 *
 * @see \Drupal\zchat\Entity\ZchatMessage.
 */
class ZchatMessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\zchat\Entity\ZchatMessageInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished zchat message entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published zchat message entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit zchat message entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete zchat message entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add zchat message entities');
  }

}
