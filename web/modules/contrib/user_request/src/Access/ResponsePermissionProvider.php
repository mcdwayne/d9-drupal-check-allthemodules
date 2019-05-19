<?php

namespace Drupal\user_request\Access;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\UncacheableEntityPermissionProvider;

/**
 * Provides permissions for responses.
 */
class ResponsePermissionProvider extends UncacheableEntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityTypePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildEntityTypePermissions($entity_type);
    $entity_type_id = $entity_type->id();
    $entity_type_plural_label = $entity_type->getPluralLabel();

    // Permissions to add responses are not needed as they permission to respond 
    // requests is tested instead.
    unset($permissions["create $entity_type_id"]);

    // Adds permission to view received responses.
    $permissions["view received $entity_type_id"] = [
      'title' => $this->t('View received @type', [
        '@type' => $entity_type_plural_label,
      ]),
    ];

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildBundlePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildBundlePermissions($entity_type);
    $entity_type_id = $entity_type->id();
    $entity_type_plural_label = $entity_type->getPluralLabel();

    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    foreach ($bundles as $bundle_name => $bundle_info) {
      // Permissions to add responses are not needed as they permission to 
      // respond requests is tested instead.
      unset($permissions["create $bundle_name $entity_type_id"]);

      // Adds permission to view received responses.
      $permissions["view received $bundle_name $entity_type_id"] = [
        'title' => $this->t('@bundle: View received @type', [
          '@bundle' => $bundle_info['label'],
          '@type' => $entity_type_plural_label,
        ]),
      ];
    }

    return $permissions;
  }

}
