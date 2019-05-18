<?php

namespace Drupal\drd\Entity\AccessControlHandler;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Release entity.
 *
 * @see \Drupal\drd\Entity\Release.
 */
class Release extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /* @var \Drupal\drd\Entity\ReleaseInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'drd.view unpublished release entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'drd.view published release entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'drd.edit release entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'drd.delete release entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'drd.add release entities');
  }

}
