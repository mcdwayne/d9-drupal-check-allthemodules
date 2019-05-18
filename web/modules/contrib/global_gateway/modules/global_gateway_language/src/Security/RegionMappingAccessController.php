<?php

namespace Drupal\global_gateway_language\Security;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class RegionMappingAccessController.
 *
 * @package Drupal\global_gateway_language\Security
 */
class RegionMappingAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return AccessResult::allowedIfHasPermission($account, 'administer region languages');
  }

}
