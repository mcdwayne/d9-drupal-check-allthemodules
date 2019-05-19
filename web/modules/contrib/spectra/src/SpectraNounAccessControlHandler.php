<?php

namespace Drupal\spectra;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Spectra noun entity.
 *
 * @see \Drupal\spectra\Entity\SpectraNoun.
 */
class SpectraNounAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\spectra\Entity\SpectraNounInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view spectra noun entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit spectra noun entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete spectra noun entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add spectra noun entities');
  }

}
