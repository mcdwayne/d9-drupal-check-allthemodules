<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the ElasticIp entity.
 *
 * @see \Drupal\aws_cloud\Entity\Ec2\ElasticIp\Entity\ElasticIp.
 */
class ElasticIpAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['view aws cloud elastic ip', 'view ' . $entity->getCloudContext()]);

      case 'update':
      case 'edit':
        return AccessResult::allowedIfHasPermissions($account, ['edit aws cloud elastic ip', 'view ' . $entity->getCloudContext()]);

      case 'delete':
        if ($entity->getAssociationId() == NULL) {
          return AccessResult::allowedIfHasPermissions($account, ['delete aws cloud elastic ip', 'view ' . $entity->getCloudContext()]);
        }
        break;

      case 'associate':
        if ($entity->getAssociationId() == NULL) {
          return AccessResult::allowedIfHasPermissions($account, [
            'edit aws cloud elastic ip',
            'view ' . $entity->getCloudContext(),
          ]);
        }
        break;

      case 'disassociate':
        if ($entity->getAssociationId() != NULL) {
          return AccessResult::allowedIfHasPermissions($account, [
            'edit aws cloud elastic ip',
            'view ' . $entity->getCloudContext(),
          ]);
        }
        break;
    }
    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * Not being used anymore.
   *
   * Access check is performed in
   * \Drupal\cloud\Controller\CloudConfigController::access.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add aws cloud elastic ip');
  }

}
