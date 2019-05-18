<?php

namespace Drupal\facture;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Entreprise entity.
 *
 * @see \Drupal\facture\Entity\Entreprise.
 */
class EntrepriseAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\facture\Entity\EntrepriseInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished entreprise entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published entreprise entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit entreprise entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete entreprise entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add entreprise entities');
  }

}
