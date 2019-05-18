<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Volume entity.
 *
 * @see \Drupal\aws_cloud\Entity\Ec2\Volume\Entity\Volume.
 */
class VolumeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    if (!AccessResult::allowedIfHasPermission($account, 'view ' . $entity->getCloudContext())->isAllowed()) {
      return AccessResult::neutral();
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view any aws cloud volume')) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIf(
            $account->hasPermission('view own aws cloud volume') &&
            ($account->id() == $entity->getOwner()->id())
          );
        }
        break;

      case 'update':
      case 'edit':
        if ($account->hasPermission('edit any aws cloud volume')) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIf(
            $account->hasPermission('edit own aws cloud volume') &&
            ($account->id() == $entity->getOwner()->id())
          );
        }
        break;

      case 'delete':
        if ($account->hasPermission('delete any aws cloud volume')) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIf(
            $account->hasPermission('delete own aws cloud volume') &&
            ($account->id() == $entity->getOwner()->id())
          );
        }
        break;

      case 'attach':
        if ($entity->getState() == 'available') {
          if ($account->hasPermission('edit any aws cloud volume')) {
            return AccessResult::allowed();
          }
          else {
            return AccessResult::allowedIf(
              $account->hasPermission('edit own aws cloud volume') &&
              ($account->id() == $entity->getOwner()->id())
            );
          }
        }
        break;

      case 'detach':
        if ($entity->getState() == 'in-use') {
          if ($account->hasPermission('edit any aws cloud volume')) {
            return AccessResult::allowed();
          }
          else {
            return AccessResult::allowedIf(
              $account->hasPermission('edit own aws cloud volume') &&
              ($account->id() == $entity->getOwner()->id())
            );
          }
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
    return AccessResult::allowedIfHasPermission($account, 'add aws cloud volume');
  }

}
