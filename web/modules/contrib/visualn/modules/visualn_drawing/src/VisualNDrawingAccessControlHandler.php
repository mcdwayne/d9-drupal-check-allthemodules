<?php

namespace Drupal\visualn_drawing;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Access controller for the VisualN Drawing entity.
 *
 * @see \Drupal\visualn_drawing\Entity\VisualNDrawing.
 */
class VisualNDrawingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer visualn drawing entities')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $type = $entity->bundle();
    $is_owner = ($account->id() && $account->id() === $entity->getOwnerId());
    switch ($operation) {
      // @todo: cache permissions?
      case 'view':
        // @todo: review
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished visualn drawing entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published visualn drawing entities');

      case 'update':
        if ($account->hasPermission('edit any ' . $type . ' visualn drawing')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('edit own ' . $type . ' visualn drawing') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: '$type: edit any visualn drawing' OR '$type: edit own visualn drawing'.")->cachePerPermissions();

      case 'delete':
        if ($account->hasPermission('delete any ' . $type . ' visualn drawing')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('delete own ' . $type . ' visualn drawing') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: '$type: delete any visualn drawing' OR '$type: delete own visualn drawing'.")->cachePerPermissions();

      default:
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // @todo: see NodeAccessControlHandler::checkFieldAccess()

    // Only users with the administer visualn drawing entities permission can edit administrative
    // fields.
    $administrative_fields = ['user_id', 'status'];
    //$administrative_fields = ['uid', 'status', 'created', 'promote', 'sticky'];
    if ($operation == 'edit' && in_array($field_definition->getName(), $administrative_fields, TRUE)) {
      return AccessResult::allowedIfHasPermission($account, 'administer visualn drawing entities');
    }

    // Users have access to the revision_log field either if they have
    // administrative permissions or if the new revision option is enabled.
    if ($operation == 'edit' && $field_definition->getName() == 'revision_log_message') {
      if ($account->hasPermission('administer visualn drawing entities')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      return AccessResult::allowedIf($items->getEntity()->type->entity->shouldCreateNewRevision())->cachePerPermissions();
      //return AccessResult::allowedIf($items->getEntity()->type->entity->isNewRevision())->cachePerPermissions();
    }

    // @todo: also add thumbnail field specific checks

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // @todo: rename 'add to 'create' permissions
    $permissions = [
      'administer visualn drawing entities',
      'add visualn drawing entities',
    ];
    if ($entity_bundle) {
      $permissions[] = 'create ' . $entity_bundle . ' visualn drawing';
    }
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
