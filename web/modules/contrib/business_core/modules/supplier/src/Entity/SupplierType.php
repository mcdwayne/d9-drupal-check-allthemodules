<?php

namespace Drupal\supplier\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\supplier\SupplierTypeInterface;

/**
 * Defines the Supplier type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "supplier_type",
 *   label = @Translation("Supplier type"),
 *   handlers = {
 *     "access" = "Drupal\supplier\SupplierTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\supplier\SupplierTypeForm",
 *       "delete" = "Drupal\supplier\Form\SupplierTypeDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\supplier\SupplierTypeListBuilder",
 *   },
 *   admin_permission = "administer supplier types",
 *   config_prefix = "type",
 *   bundle_of = "supplier",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/supplier/type/{supplier_type}",
 *     "delete-form" = "/admin/supplier/type/{supplier_type}/delete",
 *     "collection" = "/admin/supplier/type",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class SupplierType extends ConfigEntityBundleBase implements SupplierTypeInterface {

  /**
   * The machine name of this Supplier type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the Supplier type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Supplier type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('supplier.type.locked');
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
