<?php

namespace Drupal\graph_reference;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Graph edge entity.
 *
 * @see \Drupal\graph_reference\Entity\GraphEdge.
 */
class GraphEdgeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\graph_reference\Entity\GraphEdgeInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished graph edge entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published graph edge entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit graph edge entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete graph edge entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add graph edge entities');
  }

}
