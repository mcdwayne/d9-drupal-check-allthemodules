<?php

namespace Drupal\image_field_permissions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field_permissions\FieldPermissionsService;
use Drupal\field_permissions\Plugin\FieldPermissionTypeInterface;
use Drupal\image_field_permissions\Plugin\FieldPermissionType\AdvancedCustomAccess;

/**
 * Class ImageFieldPermissionsPermissionsService.
 *
 * @package Drupal\image_field_permissions
 */
class ImageFieldPermissionsPermissionsService extends FieldPermissionsService {
  /**
   * Get access for pseudo-field by operations and account permissions.
   *
   * @param string $operation
   *    String operation on field.
   * @param EntityInterface $entity
   *   The entity object on which to check access.
   * @param AccountInterface $account
   *    Account to get permissions.
   * @param FieldDefinitionInterface $field_definition
   *   Fields to get permissions.
   * @param string $type
   *   Pseudo-field type.
   *
   * @return bool
   *   Checking access result.
   */
  public function getPseudoFieldAccess($operation, EntityInterface $entity, AccountInterface $account, FieldDefinitionInterface $field_definition, $type) {
    $definition = $field_definition->getFieldStorageDefinition();
    $permission_type = $this->fieldGetPermissionType($definition);
    if (
      in_array('administrator', $account->getRoles())
      ||
      $permission_type == FieldPermissionTypeInterface::ACCESS_PUBLIC
    ) {
      return TRUE;
    }
    // Pass access control to the plugin.
    /** @var AdvancedCustomAccess $plugin */
    $plugin = $this->permissionTypeManager
      ->createInstance($permission_type, [], $field_definition->getFieldStorageDefinition());
    return $plugin->hasPseudoFieldAccess($operation, $entity, $account, $type, $field_definition->getFieldStorageDefinition()->getName());
  }

  /**
   * {@inheritdoc}
   */
  public static function getList($field_label = '') {
    return [
      'create' => [
        'label' => t('Create field'),
        'title' => t('Upload own image file for field @field', ['@field' => $field_label]),
      ],
      'edit own' => [
        'label' => t('Edit own field'),
        'title' => t('Edit own image file for field @field', ['@field' => $field_label]),
      ],
      'edit' => [
        'label' => t('Edit field'),
        'title' => t("Edit anyone's own image file for field @field", ['@field' => $field_label]),
      ],
      'view own' => [
        'label' => t('View own field'),
        'title' => t('View own image file for field @field', ['@field' => $field_label]),
      ],
      'view' => [
        'label' => t('View field'),
        'title' => t("View anyone's image file for field @field", ['@field' => $field_label]),
      ],
    ];
  }

}
