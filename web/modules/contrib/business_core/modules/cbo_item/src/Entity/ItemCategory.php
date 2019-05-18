<?php

namespace Drupal\cbo_item\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\cbo_item\ItemCategoryInterface;

/**
 * Defines the Item category configuration entity.
 *
 * @ConfigEntityType(
 *   id = "item_category",
 *   label = @Translation("Item category"),
 *   handlers = {
 *     "access" = "Drupal\cbo_item\ItemCategoryAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\cbo_item\ItemCategoryForm",
 *       "delete" = "Drupal\cbo_item\Form\ItemCategoryDeleteConfirm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\cbo_item\ItemCategoryListBuilder",
 *   },
 *   admin_permission = "administer item categories",
 *   config_prefix = "category",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/item/category/add",
 *     "canonical" = "/admin/item/category/{item_category}",
 *     "edit-form" = "/admin/item/category/{item_category}/edit",
 *     "delete-form" = "/admin/item/category/{item_category}/delete",
 *     "collection" = "/admin/item/category",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class ItemCategory extends ConfigEntityBase implements ItemCategoryInterface {

  /**
   * The machine name of this Item category.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the Item category.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Item category.
   *
   * @var string
   */
  protected $description;

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
