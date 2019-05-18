<?php

namespace Drupal\owntracks\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for owntracks entities.
 */
class OwnTracksEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = [$this->entityType->getAdminPermission(), 'create owntracks entities'];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = AccessResult::allowedIfHasPermissions($account, [$this->entityType->getAdminPermission(), $operation . ' any owntracks entity'], 'OR');

    if (!$access->isAllowed() && $account->id() === $entity->getOwnerId()) {
      return $access->orIf(AccessResult::allowedIfHasPermission($account, $operation . ' own owntracks entities')
        ->cachePerUser()->addCacheableDependency($entity));
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if ($operation === 'edit' && $field_definition->getName() === 'uid') {
      return AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
