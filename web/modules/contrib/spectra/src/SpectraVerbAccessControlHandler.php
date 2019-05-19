<?php

namespace Drupal\spectra;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Spectra verb entity.
 *
 * @see \Drupal\spectra\Entity\SpectraVerb.
 */
class SpectraVerbAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\spectra\Entity\SpectraVerbInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view spectra verb entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit spectra verb entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete spectra verb entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add spectra verb entities');
  }

}
