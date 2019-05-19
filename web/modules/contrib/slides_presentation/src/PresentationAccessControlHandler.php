<?php

namespace Drupal\slides_presentation;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Presentation entity.
 *
 * @see \Drupal\slides_presentation\Entity\Presentation.
 */
class PresentationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view presentation entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit presentation entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete presentation entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add presentation entities');
  }

}
