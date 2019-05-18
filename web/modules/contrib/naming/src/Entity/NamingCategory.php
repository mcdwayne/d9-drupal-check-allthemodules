<?php

/**
 * @file
 * Contains Drupal\naming\Entity\NamingCategory.
 */

namespace Drupal\naming\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\naming\NamingCategoryInterface;

/**
 * Defines the NamingCategory entity.
 *
 * @ConfigEntityType(
 *   id = "naming_category",
 *   label = @Translation("Naming category"),
 *   admin_permission = "administer naming categories",
 *   config_prefix = "category",
 *   handlers = {
 *     "list_builder" = "Drupal\naming\NamingCategoryListBuilder",
 *     "form" = {
 *       "add" = "Drupal\naming\NamingCategoryForm",
 *       "edit" = "Drupal\naming\NamingCategoryForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/development/naming/category/add",
 *     "edit-form" = "/admin/config/development/naming/category/manage/{naming_category}",
 *     "delete-form" = "/admin/config/development/naming/category/{naming_category}/delete",
 *     "collection" = "/admin/config/development/naming/category/manage",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "content",
 *     "weight",
 *   }
 * )
 */
class NamingCategory extends ConfigEntityBase implements NamingCategoryInterface {

  /**
   * The NamingCategory id.
   *
   * @var string
   */
  protected $id;

  /**
   * The NamingCategory UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The NamingCategory label.
   *
   * @var string
   */
  protected $label;

  /**
   * The NamingCategory format.
   *
   * @var array
   */
  protected $content = [
    'value' => '',
    'format' => '',
  ];

  /**
   * The NamingCategory weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->content ?: [
      'value' => '',
      'format' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight ?: 0;
  }

}
