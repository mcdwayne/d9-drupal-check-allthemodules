<?php

namespace Drupal\taxonomy_access_fix;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermAccessControlHandler;

/**
 * Defines the access control handler for the taxonomy term entity type.
 *
 * @see \Drupal\taxonomy\Entity\Term
 */
class TermAccessFixTermControlHandler extends TermAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        // TAF module: restricted view access.
        return AccessResult::allowedIfHasPermissions($account, [
          "view terms in {$entity->bundle()}",
          'administer taxonomy',
        ], 'OR');

      default:
        // Drupal core taxonomy: all other operations.
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
