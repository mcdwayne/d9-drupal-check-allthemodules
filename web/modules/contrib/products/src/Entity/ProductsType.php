<?php

namespace Drupal\products\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Products type entity.
 *
 * @ConfigEntityType(
 *   id = "products_type",
 *   label = @Translation("Products type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\products\ProductsTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\products\Form\ProductsTypeForm",
 *       "edit" = "Drupal\products\Form\ProductsTypeForm",
 *       "delete" = "Drupal\products\Form\ProductsTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\products\ProductsTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "products",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/idcp/products/type/{products_type}",
 *     "add-form" = "/admin/idcp/products/type/add",
 *     "edit-form" = "/admin/idcp/products/type/{products_type}/edit",
 *     "delete-form" = "/admin/idcp/products/type/{products_type}/delete",
 *     "collection" = "/admin/idcp/products/type"
 *   }
 * )
 */
class ProductsType extends ConfigEntityBundleBase implements ProductsTypeInterface {

  /**
   * The Products type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Products type label.
   *
   * @var string
   */
  protected $label;

}
