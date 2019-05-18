<?php

namespace Drupal\assembly;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Assembly entity.
 *
 * @see \Drupal\assembly\Entity\Assembly.
 */
class AssemblyTypeAccessControlHandler extends EntityAccessControlHandler {
  /**
   * Allow access to assembly type label.
   *
   * @var bool
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\assembly\Entity\AssemblyInterface $entity */
    if ($operation == 'view label') {
      return AccessResult::allowed();
    }

    // Unknown operation, no opinion.
    return parent::checkAccess($entity, $operation, $account);
  }
}
