<?php

namespace Drupal\image_field_permissions\Plugin\FieldPermissionType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\field_permissions\Plugin\FieldPermissionType\CustomAccess;
use Drupal\image_field_permissions\ImageFieldPermissionsPermissionsService;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Class AdvancedCustomAccess.
 *
 * @FieldPermissionType(
 *   id = "custom",
 *   title = @Translation("Custom permissions"),
 *   description = @Translation("Define custom permissions for this field."),
 *   weight = 50
 * )
 *
 * @package Drupal\image_field_permissions\Plugin\FieldPermissionType
 */
class AdvancedCustomAccess extends CustomAccess {
  /**
   * Checks pseudo-fields access.
   */
  public function hasPseudoFieldAccess($operation, EntityInterface $entity, AccountProxyInterface $account, $pseudo_field_type, $field_name) {
    $op = implode(' ', [$operation, $pseudo_field_type, $field_name]);
    $own_op = implode(' ', [$operation, 'own', $pseudo_field_type, $field_name]);

    if ($account->hasPermission($op)) {
      return TRUE;
    }
    else {
      // User entities don't implement `EntityOwnerInterface`.
      if ($entity instanceof UserInterface) {
        return $entity->id() == $account->id()
        && $account->hasPermission($own_op);
      }
      elseif ($entity instanceof EntityOwnerInterface) {
        return $entity->getOwnerId() == $account->id()
        && $account->hasPermission($own_op);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions = [];
    $field_name = $this->fieldStorage->getName();
    $permission_list = ImageFieldPermissionsPermissionsService::getList($field_name);
    // Add an additional permissions for image field types.
    if ($this->fieldStorage->getType() == 'image') {
      $permission_list['edit own alt'] = [
        'label' => $this->t('Edit own image alt'),
        'title' => $this->t('Edit own image alt value for field @field', ['@field' => $field_name]),
      ];
      $permission_list['edit alt'] = [
        'label' => $this->t('Edit image alt'),
        'title' => $this->t('Edit image alt value for field @field', ['@field' => $field_name]),
      ];
      $permission_list['edit own title'] = [
        'label' => $this->t('Edit own image title'),
        'title' => $this->t('Edit own image title value for field @field', ['@field' => $field_name]),
      ];
      $permission_list['edit title'] = [
        'label' => $this->t('Edit image title'),
        'title' => $this->t('Edit image title value for field @field', ['@field' => $field_name]),
      ];
    }
    $perms_name = array_keys($permission_list);
    foreach ($perms_name as $perm_name) {
      $name = $perm_name . ' ' . $field_name;
      $permissions[$name] = $permission_list[$perm_name];
    }
    return $permissions;
  }

}
