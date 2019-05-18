<?php

namespace Drupal\flags\Security;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the flag mapping entity.
 *
 * We set this class to be the access controller in FlagMapping's entity annotation.
 *
 * @see \Drupal\flags\Entity\FlagMapping
 *
 * @ingroup flags
 */
class FlagMappingAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'administer flag mapping');
  }

}
