<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Image entity.
 *
 * @see \Drupal\aws_cloud\Entity\Ec2\Image\Entity\Image.
 */
class ImageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    if (!AccessResult::allowedIfHasPermission($account, 'view ' . $entity->getCloudContext())->isAllowed()) {
      return AccessResult::neutral();
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view any aws cloud image')) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIf(
            $account->hasPermission('view own aws cloud image') &&
            ($account->id() == $entity->getOwner()->id())
          );
        }
        break;

      case 'update':
      case 'edit':
        if ($account->hasPermission('edit any aws cloud image')) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIf(
            $account->hasPermission('edit own aws cloud image') &&
            ($account->id() == $entity->getOwner()->id())
          );
        }
        break;

      case 'delete':
        if ($account->hasPermission('delete any aws cloud image')) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIf(
            $account->hasPermission('delete own aws cloud image') &&
            ($account->id() == $entity->getOwner()->id())
          );
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
    return AccessResult::allowedIfHasPermission($account, 'add aws cloud image');
  }

}
