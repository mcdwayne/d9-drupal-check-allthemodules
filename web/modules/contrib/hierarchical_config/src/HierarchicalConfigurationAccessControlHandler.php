<?php

/**
 * @file
 * Contains \Drupal\hierarchical_config\HierarchicalConfigurationAccessControlHandler.
 */

namespace Drupal\hierarchical_config;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Hierarchical configuration entity.
 *
 * @see \Drupal\hierarchical_config\Entity\HierarchicalConfiguration.
 */
class HierarchicalConfigurationAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\hierarchical_config\HierarchicalConfigurationInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished hierarchical configuration entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published hierarchical configuration entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit hierarchical configuration entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete hierarchical configuration entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add hierarchical configuration entities');
  }

}
