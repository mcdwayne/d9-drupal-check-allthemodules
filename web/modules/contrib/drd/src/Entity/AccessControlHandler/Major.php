<?php

namespace Drupal\drd\Entity\AccessControlHandler;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Major Version entity.
 *
 * @see \Drupal\drd\Entity\Major.
 */
class Major extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /* @var \Drupal\drd\Entity\MajorInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'drd.view unpublished major version entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'drd.view published major version entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'drd.edit major version entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'drd.delete major version entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'drd.add major version entities');
  }

}
