<?php

namespace Drupal\aggrid;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the aggrid configuration entity.
 *
 * We set this class to be the access controller in ag-Grid's entity annotation.
 *
 * @see \Drupal\aggrid\Entity\Aggrid
 *
 * @ingroup aggrid
 */
class AggridAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      return AccessResult::allowed();
    }

    // Check administer access rule or administer aggrid config entities
    // and grant full access to user.
    if ($account->hasPermission('administrator') ||
      $account->hasPermission('administer aggrid config entities')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
