<?php

namespace Drupal\cbo_inventory\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\cbo_inventory\SubinventoryTypeInterface;

/**
 * Defines the Subinventory type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "subinventory_type",
 *   label = @Translation("Subinventory type"),
 *   handlers = {
 *     "access" = "Drupal\cbo_inventory\SubinventoryTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\cbo_inventory\SubinventoryTypeForm",
 *       "delete" = "Drupal\cbo_inventory\Form\SubinventoryTypeDeleteConfirm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\cbo\CboConfigEntityListBuilder",
 *   },
 *   admin_permission = "administer subinventory types",
 *   config_prefix = "type",
 *   bundle_of = "subinventory",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/subinventory/type/add",
 *     "edit-form" = "/admin/subinventory/type/{subinventory_type}",
 *     "delete-form" = "/admin/subinventory/type/{subinventory_type}/delete",
 *     "collection" = "/admin/subinventory/type",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class SubinventoryType extends ConfigEntityBundleBase implements SubinventoryTypeInterface {

  /**
   * The machine name of this Subinventory type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the Subinventory type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Subinventory type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('subinventory.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
