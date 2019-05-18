<?php

namespace Drupal\linkchecker;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the linkchecker link entity type.
 *
 * @see \Drupal\linkchecker\Entity\LinkCheckerLink
 */
class LinkCheckerLinkAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\linkchecker\LinkCheckerLinkInterface $entity */
    if ($account->hasPermission('administer linkchecker')
      || $account->hasPermission('edit linkchecker link settings')) {
      $parentEntity = $entity->getParentEntity();

      if (isset($parentEntity) && !$parentEntity->access($operation, $account)) {
        return AccessResult::forbidden()
          ->addCacheableDependency($parentEntity)
          ->cachePerPermissions();
      }

      if (!$parentEntity->hasField($entity->getParentEntityFieldName())) {
        return AccessResult::forbidden()
          ->addCacheableDependency($parentEntity)
          ->cachePerPermissions();
      }

      $parentEntityField = $parentEntity->get($entity->getParentEntityFieldName());
      if (!$parentEntityField->access($operation, $account)) {
        return AccessResult::forbidden()
          ->addCacheableDependency($parentEntity)
          ->cachePerPermissions();
      }

      return AccessResult::allowed()
        ->addCacheableDependency($parentEntity)
        ->cachePerPermissions();
    }

    // The permission is required.
    return AccessResult::forbidden()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // No user can change read only fields.
    if ($operation == 'edit') {
      switch ($field_definition->getName()) {
        case 'method':
        case 'status':
          return AccessResult::allowedIfHasPermissions($account, [
            'administer linkchecker',
            'edit linkchecker link settings',
          ], 'OR');

        default:
          return AccessResult::forbidden();
      }
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
