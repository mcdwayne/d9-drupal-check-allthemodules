<?php

namespace Drupal\dcat;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Distribution entity.
 *
 * @see \Drupal\dcat\Entity\DcatDistribution.
 */
class DcatDistributionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dcat\Entity\DcatDistributionInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished distribution entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published distribution entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit distribution entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete distribution entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add distribution entities');
  }

}
