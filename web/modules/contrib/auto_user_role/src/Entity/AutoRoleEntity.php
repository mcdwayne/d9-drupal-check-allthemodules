<?php

namespace Drupal\auto_user_role\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Auto role entity entity.
 *
 * @ConfigEntityType(
 *   id = "auto_role_entity",
 *   label = @Translation("Auto role"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\auto_user_role\AutoRoleEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\auto_user_role\Form\AutoRoleEntityForm",
 *       "edit" = "Drupal\auto_user_role\Form\AutoRoleEntityForm",
 *       "delete" = "Drupal\auto_user_role\Form\AutoRoleEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\auto_user_role\AutoRoleEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "auto_role_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "role" = "role",
 *     "field" = "field",
 *     "field_value" = "field_value",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/auto_role_entity/{auto_role_entity}",
 *     "add-form" = "/admin/structure/auto_role_entity/add",
 *     "edit-form" = "/admin/structure/auto_role_entity/{auto_role_entity}/edit",
 *     "delete-form" = "/admin/structure/auto_role_entity/{auto_role_entity}/delete",
 *     "collection" = "/admin/structure/auto_role_entity"
 *   }
 * )
 */
class AutoRoleEntity extends ConfigEntityBase implements AutoRoleEntityInterface {

  /**
   * The Auto role entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Auto role entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Role entity ID.
   *
   * @var string
   */
  protected $role;

  /**
   * The Field ID.
   *
   * @var string
   */
  protected $field;

  /**
   * The Field value.
   *
   * @var string
   */
  protected $field_value;

  /**
   * @return string
   */
  public function getRole() {
    return $this->role;
  }

  /**
   * @param string $role
   * @return AutoRoleEntity
   */
  public function setRole($role) {
    $this->role = $role;
    return $this;
  }

  /**
   * @return string
   */
  public function getField() {
    return $this->field;
  }

  /**
   * @param string $field
   * @return AutoRoleEntity
   */
  public function setField($field) {
    $this->field = $field;
    return $this;
  }

  /**
   * @return string
   */
  public function getFieldValue() {
    return $this->field_value;
  }

  /**
   * @param string $field_value
   * @return AutoRoleEntity
   */
  public function setFieldValue($field_value) {
    $this->field_value = $field_value;
    return $this;
  }

}
