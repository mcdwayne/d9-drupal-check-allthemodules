<?php

namespace Drupal\permission_ui\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the user permission entity class.
 *
 * @ConfigEntityType(
 *   id = "user_permission",
 *   label = @Translation("Permission"),
 *   handlers = {
 *     "storage" = "Drupal\permission_ui\PermissionStorage",
 *     "access" = "Drupal\permission_ui\PermissionAccessControlHandler",
 *     "list_builder" = "Drupal\permission_ui\PermissionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\permission_ui\Form\PermissionForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer permissions ui",
 *   config_prefix = "permissions",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "title" = "title",
 *     "is_restricted" = "is_restricted",
 *     "entity_type" = "entity_type",
 *     "bundle_type" = "bundle_type",
 *     "operation" = "operation",
 *     "scope" = "scope",
 *   },
 *   links = {
 *     "delete-form" = "/admin/people/permission_ui/manage/{user_permission}/delete",
 *     "edit-form" = "/admin/people/permission_ui/manage/{user_permission}",
 *     "collection" = "/admin/people/permission_ui"
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "entity_type",
 *     "bundle_type",
 *     "operation",
 *     "scope",
 *     "description",
 *     "is_restricted"
 *   }
 * )
 */
class Permission extends ConfigEntityBase {

  /**
   * Entity type.
   *
   * @var string
   *   Entity type.
   */
  protected $entity_type;

  /**
   * Bundle type.
   *
   * @var string
   *   String bundle type.
   */
  protected $bundle_type;

  /**
   * Entity operation.
   *
   * @var string
   *   String operation. e.g. Add, Edit, Delete.
   */
  protected $operation;

  /**
   * Scope of an operation.
   *
   * @var string
   *   String scope. Allowed values are Any or Own.
   */
  protected $scope;

  /**
   * The machine name of this permission.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label of this permission.
   *
   * @var string
   */
  protected $title;

  /**
   * The description of a permission.
   *
   * @var string
   */
  protected $description;

  /**
   * An indicator whether the permission is restricted.
   *
   * @var bool
   */
  protected $is_restricted;

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->id = $this->computeId();
    $this->title = $this->getTitle();
    parent::save();
  }

  /**
   * Compute ID.
   *
   * @return string
   *   String ID.
   */
  protected function computeId() {
    $elements = [
      $this->operation,
      $this->scope,
      $this->bundle_type,
      $this->entity_type,
    ];
    return implode('_', array_filter($elements));
  }

  /**
   * Gives entity type label.
   *
   * @return string
   *   String label value.
   */
  public function getEntityTypeLabel() {
    return \Drupal::entityTypeManager()->getDefinition($this->entity_type)->getLabel();
  }

  /**
   * Gives bundle label.
   *
   * @return string|null
   *   String bundle label.
   */
  public function getBundleLabel() {
    /* @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    return $bundle_info->getBundleInfo($this->entity_type)[$this->bundle_type]['label'];
  }

  /**
   * Getter for bundle type.
   *
   * @return string
   *   Bundle type machine name.
   */
  public function getElementBundleType() {
    return $this->bundle_type;
  }

  /**
   * Getter for entity type.
   *
   * @return string
   *   Entity type machine name.
   */
  public function getElementEntityType() {
    return $this->entity_type;
  }

  /**
   * Get title.
   *
   * @return string|null
   *   Description.
   */
  public function getTitle() {
    $elements = [
      $this->getBundleLabel() . ':',
      $this->getOperation(),
      $this->getScope(),
      $this->getEntityTypeLabel(),
    ];
    return implode(' ', $elements);
  }

  /**
   * Checks permission has restricted access or not.
   *
   * @return bool
   *   TRUE, if restricted. FALSE otherwise.
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * Checks permission has restricted access or not.
   *
   * @return bool
   *   TRUE, if restricted. FALSE otherwise.
   */
  public function getScope() {
    return $this->scope;
  }

  /**
   * Checks permission has restricted access or not.
   *
   * @return bool
   *   TRUE, if restricted. FALSE otherwise.
   */
  public function isRestricted() {
    return $this->is_restricted;
  }

  /**
   * Description.
   *
   * @return string|null
   *   Description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Gets permission array.
   *
   * @return array
   *   An array of permission.
   */
  public function toPermissionApi() {
    return [
      'title' => $this->getTitle(),
      'description' => $this->getDescription(),
      'provider' => $this->getElementEntityType(),
      'restrict access' => $this->isRestricted(),
    ];
  }

}
