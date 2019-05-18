<?php

namespace Drupal\cloudconvert;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the CloudConvert Task entity.
 *
 * @see \Drupal\cloudconvert\Entity\CloudConvertTask.
 */
class CloudConvertTaskAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published cloudconvert task entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit cloudconvert task entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete cloudconvert task entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add cloudconvert task entities');
  }

}
