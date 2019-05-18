<?php

namespace Drupal\entity_list\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class EntityListAccessControlHandler.
 *
 * @package Drupal\entity_list\Access
 */
class EntityListAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);
    if ($operation == 'view') {
      return $access->orIf(AccessResult::allowedIfHasPermission($account, 'view entity list'));
    }
    return $access->orIf(AccessResult::allowedIfHasPermission($account, 'administer entity list'));
  }

}
