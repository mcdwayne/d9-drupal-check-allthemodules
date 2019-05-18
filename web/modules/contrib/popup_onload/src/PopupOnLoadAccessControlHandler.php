<?php

namespace Drupal\popup_onload;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Popup On Load entity.
 *
 * @see \Drupal\popup_onload\Entity\PopupOnLoad.
 */
class PopupOnLoadAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\popup_onload\Entity\PopupOnLoadInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished popup on load entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published popup on load entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit popup on load entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete popup on load entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add popup on load entities');
  }

}
