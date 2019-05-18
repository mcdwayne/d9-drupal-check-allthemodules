<?php

namespace Drupal\entity_generic\Permission;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\EntityPermissionProvider;
use Drupal\entity_generic\Entity\EntityApprovedInterface;
use Drupal\entity_generic\Entity\EntityArchivedInterface;
use Drupal\entity_generic\Entity\EntityDeletedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides generic entity permissions.
 *
 * @see \Drupal\entity\EntityPermissionProvider
 */
class GenericPermissionProvider extends EntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityTypePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildEntityTypePermissions($entity_type);
    $entity_type_id = $entity_type->id();
    $singular_label = $entity_type->getSingularLabel();
    $plural_label = $entity_type->getPluralLabel();
    $has_owner = $entity_type->entityClassImplements(EntityOwnerInterface::class) && $entity_type->hasKey('uid');
    $archived_flow = $entity_type->entityClassImplements(EntityArchivedInterface::class) && $entity_type->hasKey('archived');
    $approved_flow = $entity_type->entityClassImplements(EntityApprovedInterface::class) && $entity_type->hasKey('approved');
    $deleted_flow = $entity_type->entityClassImplements(EntityDeletedInterface::class) && $entity_type->hasKey('deleted');

    // Additional permissions for entities with owner flow.
    if ($has_owner) {
      $permissions["view any {$entity_type_id}"] = [
        'title' => $this->t('View any @type', [
          '@type' => $singular_label,
        ]),
      ];
      $permissions["view own {$entity_type_id}"] = [
        'title' => $this->t('View own @type', [
          '@type' => $plural_label,
        ]),
      ];
    }
    else {
      $permissions["view {$entity_type_id}"] = [
        'title' => $this->t('View @type', [
          '@type' => $plural_label,
        ]),
      ];
    }

    // Additional permissions for "approved" flow.
    if ($approved_flow) {
      $permissions["approve any {$entity_type_id}"] = [
        'title' => $this->t('Approve any @type', [
          '@type' => $singular_label,
        ]),
      ];
      $permissions["approve own {$entity_type_id}"] = [
        'title' => $this->t('Approve own @type', [
          '@type' => $plural_label,
        ]),
      ];
    }

    // Additional permissions for "archived" flow.
    if ($archived_flow) {
      $permissions["archive any {$entity_type_id}"] = [
        'title' => $this->t('Archive any @type', [
          '@type' => $singular_label,
        ]),
      ];
      $permissions["archive own {$entity_type_id}"] = [
        'title' => $this->t('Archive own @type', [
          '@type' => $plural_label,
        ]),
      ];
    }

    // Additional permissions for "deleted" flow.
    if ($deleted_flow) {
      $permissions["mark deleted any {$entity_type_id}"] = [
        'title' => $this->t('Mark as deleted any @type', [
          '@type' => $singular_label,
        ]),
      ];
      $permissions["mark deleted own {$entity_type_id}"] = [
        'title' => $this->t('Mark as deleted own @type', [
          '@type' => $plural_label,
        ]),
      ];
    }

    return $permissions;
  }

}
