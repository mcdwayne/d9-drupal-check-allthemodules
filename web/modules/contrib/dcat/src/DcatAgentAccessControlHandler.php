<?php

namespace Drupal\dcat;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Agent entity.
 *
 * @see \Drupal\dcat\Entity\DcatAgent.
 */
class DcatAgentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dcat\Entity\DcatAgentInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished agent entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published agent entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit agent entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete agent entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add agent entities');
  }

}
