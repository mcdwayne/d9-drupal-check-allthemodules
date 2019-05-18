<?php

namespace Drupal\cbo_item\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\cbo_item\ItemTypeInterface;

/**
 * Defines the Item type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "item_type",
 *   label = @Translation("Item type"),
 *   handlers = {
 *     "access" = "Drupal\cbo_item\ItemTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\cbo_item\ItemTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\cbo_item\ItemTypeListBuilder",
 *   },
 *   admin_permission = "administer item types",
 *   config_prefix = "type",
 *   bundle_of = "item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/item/type/add",
 *     "edit-form" = "/admin/item/type/{item_type}",
 *     "delete-form" = "/admin/item/type/{item_type}/delete",
 *     "collection" = "/admin/item/type",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class ItemType extends ConfigEntityBundleBase implements ItemTypeInterface {

  /**
   * The machine name of this Item type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the Item type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Item type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('item.type.locked');
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
