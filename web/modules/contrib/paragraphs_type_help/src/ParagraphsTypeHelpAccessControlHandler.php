<?php

namespace Drupal\paragraphs_type_help;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines the access control handler for the paragraphs type help entity type.
 *
 * @see \Drupal\paragraphs_type_help\Entity\ParagraphsTypeHelp
 */
class ParagraphsTypeHelpAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);

    // DENY if the parent denies.
    if ($access->isForbidden()) {
      return $access;
    }

    $admin_permission = $this->entityType->getAdminPermission();
    if ($admin_permission && $account->hasPermission($admin_permission)) {
      return AccessResult::allowed();
    }

    // Manage permissions are allowed to do any operation on the entity.
    if (in_array($operation, ['view', 'update', 'create', 'delete']) &&
        $account->hasPermission('manage paragraphs_type_help entity')) {
      return AccessResult::allowed();
    }

    // View access for everyone else.
    if ($operation === 'view') {
      return $entity->isPublished() ? AccessResult::allowed() : AccessResult::forbidden();
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage paragraphs_type_help entity');
  }

}
