<?php

namespace Drupal\abstractpermissions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Css Injector entity.
 *
 * @ConfigEntityType(
 *   id = "abstractpermissions_abstraction",
 *   config_prefix = "abstraction",
 *   label = @Translation("Permission abstraction"),
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\abstractpermissions\Form\PermissionAbstractionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\abstractpermissions\Form\PermissionAbstractionForm",
 *       "edit" = "Drupal\abstractpermissions\Form\PermissionAbstractionForm",
 *       "delete" = "Drupal\abstractpermissions\Form\PermissionAbstractionDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer permissions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/people/permissions/abstract",
 *     "add-form" = "/admin/people/permissions/abstract/add",
 *     "canonical" = "/admin/people/permissions/abstract/item/{abstractpermissions_abstraction}",
 *     "edit-form" = "/admin/people/permissions/abstract/item/{abstractpermissions_abstraction}/edit",
 *     "delete-form" = "/admin/people/permissions/abstract/item/{abstractpermissions_abstraction}/delete",
 *   }
 * )
 */
class PermissionAbstraction extends ConfigEntityBase implements PermissionAbstractionInterface {

  /**
   * The abstract permission ID.
   *
   * @var string
   */
  public $id;

  /**
   * The abstract permission label.
   *
   * @var string
   */
  public $label;

  /**
   * The abstract permission description.
   *
   * @var string
   */
  public $description;

  /**
   * The abstracted permission labels, keyed by machine name.
   *
   * @var string[]
   */
  public $abstracted_permissions;

  /**
   * The permission machine names.
   *
   * @var string[]
   */
  public $governed_permissions;

  /**
   * The permission mapping.
   *
   * @var string[][]
   */
  public $permission_mapping;

  /**
   * @inheritDoc
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @inheritDoc
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * @inheritDoc
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @inheritDoc
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * @inheritDoc
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @inheritDoc
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * @inheritDoc
   */
  public function getAbstractedPermissions() {
    return isset($this->abstracted_permissions) ? $this->abstracted_permissions : [];
  }

  /**
   * @inheritDoc
   */
  public function getAbstractedPermissionsPublicInfo() {
    $abstractionId = $this->id();
    $abstractionLabel = $this->label();
    $permissionsInfo = [];
    foreach ($this->getAbstractedPermissions() as $id => $label) {
      $permissionName = "abstractpermissions:$abstractionId:$id";
      $permissionLabel = t('[@abstraction] @label', ['@abstraction' => $abstractionLabel, '@label' => $label], ['context' => 'abstractpermissions']);
      $permissionsInfo[$permissionName] = ['title' => $permissionLabel];
    }
    return $permissionsInfo;
  }

  /**
   * @inheritDoc
   */
  public function setAbstractedPermissions($abstractedPermissions) {
    $this->abstracted_permissions = $abstractedPermissions;
  }

  /**
   * @inheritDoc
   */
  public function getGovernedPermissions() {
    return isset($this->governed_permissions) ? $this->governed_permissions : [];
  }

  /**
   * @inheritDoc
   */
  public function setGovernedPermissions($governedPermissions) {
    $this->governed_permissions = $governedPermissions;
  }

  /**
   * @inheritDoc
   */
  public function getPermissionMapping() {
    return isset($this->permission_mapping) ? $this->permission_mapping : [];
  }

  /**
   * @inheritDoc
   */
  public function setPermissionMapping($permissionMapping) {
    $this->permission_mapping = $permissionMapping;
  }

  /**
   * @inheritDoc
   */
  public function getGoverningFactor($abstractedPermissionPublicId, $governedPermissionId) {
    list(,, $abstractedPermissionId) = explode(':', $abstractedPermissionPublicId);
    return isset($this->permission_mapping[$abstractedPermissionId])
      && in_array($governedPermissionId, $this->permission_mapping[$abstractedPermissionId]);
  }

}
