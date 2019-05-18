<?php

namespace Drupal\drd\Entity\AccessControlHandler;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Host entity.
 *
 * @see \Drupal\drd\Entity\Host.
 */
class Host extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /* @var \Drupal\drd\Entity\HostInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'drd.view unpublished host entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'drd.view published host entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'drd.edit host entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'drd.delete host entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'drd.add host entities');
  }

}
