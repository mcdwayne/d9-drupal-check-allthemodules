<?php

namespace Drupal\pathed_file;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines access controller for the pathed file entity.
 *
 * @see \Drupal\pathed_file\Entity\PathedFile
 *
 * @ingroup pathed_file
 */
class PathedFileAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'access content')->cachePerPermissions();
    }
    elseif (in_array($operation, array('create', 'update', 'delete'))) {
      return AccessResult::allowedIfHasPermission($account, 'administer pathed files')->cachePerPermissions();
    }

    return parent::checkAccess($entity, $operation, $account);
  }
}
