<?php

namespace Drupal\core_extend\Entity;

/**
 * Provides a trait for interacting with entities with roles.
 */
trait EntityRolesTrait {

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    if (!$this->get('roles')->isEmpty()) {
      return array_column($this->get('roles')->getValue(), 'target_id');
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasRole($rid) {
    return in_array($rid, $this->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function addRole($rid) {
    $roles = $this->getRoles();
    $roles[] = $rid;
    $this->set('roles', array_unique($roles));
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole($rid) {
    $this->set('roles', array_diff($this->getRoles(), [$rid]));
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    $roles = $this->getRoles();
    return self::getRoleStorage()->isPermissionInRoles($permission, $roles);
  }

  /**
   * Returns the role storage object.
   *
   * @return \Drupal\core_extend\Entity\Storage\RoleEntityStorageInterface|null
   *   The role storage object.
   */
  protected static function getRoleStorage() {
    $entity_type_id = \Drupal::service('entity_type.repository')->getEntityTypeFromClass(self::class);
    $base_fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($entity_type_id);

    if (!array_key_exists('roles', $base_fields)) {
      return NULL;
    }

    $role_entity_type_id = $base_fields['roles']->getSetting('target_type');

    return \Drupal::service('entity_type.manager')->getStorage($role_entity_type_id);
  }

}
