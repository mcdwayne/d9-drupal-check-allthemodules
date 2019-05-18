<?php

namespace Drupal\css_background;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the CSS background entity.
 *
 * @see \Drupal\css_background\Entity\CssBackgroundEntity.
 */
class CssBackgroundEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\css_background\Entity\CssBackgroundEntityInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::forbidden();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit hcp css_background entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete hcp css_background entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add hcp css_background entities');
  }

}
