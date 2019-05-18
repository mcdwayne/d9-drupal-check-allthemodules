<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the KeyPair entity.
 *
 * @see \Drupal\aws_cloud\Entity\Ec2\KeyPair\Entity\KeyPair.
 */
class KeyPairAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['view aws cloud key pair', 'view ' . $entity->getCloudContext()]);

      case 'update':
      case 'edit':
        return AccessResult::allowedIfHasPermissions($account, ['edit aws cloud key pair', 'view ' . $entity->getCloudContext()]);

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete aws cloud key pair', 'view ' . $entity->getCloudContext()]);
    }
    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * Not being used anymore.
   *
   * Access check is performed in CloudConfigController::access.
   * {@deprecated}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add aws cloud key pair');
  }

}
