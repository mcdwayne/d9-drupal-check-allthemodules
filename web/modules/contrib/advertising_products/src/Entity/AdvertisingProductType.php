<?php

namespace Drupal\advertising_products\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\advertising_products\AdvertisingProductTypeInterface;

/**
 * Defines the Advertising Product type entity.
 *
 * @ConfigEntityType(
 *   id = "advertising_product_type",
 *   label = @Translation("Advertising Product type"),
 *   handlers = {
 *     "list_builder" = "Drupal\advertising_products\AdvertisingProductTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\advertising_products\Form\AdvertisingProductTypeForm",
 *       "edit" = "Drupal\advertising_products\Form\AdvertisingProductTypeForm",
 *       "delete" = "Drupal\advertising_products\Form\AdvertisingProductTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\advertising_products\AdvertisingProductTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "advertising_product_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "advertising_product",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/advertising_product_type/{advertising_product_type}",
 *     "add-form" = "/admin/structure/advertising_product_type/add",
 *     "edit-form" = "/admin/structure/advertising_product_type/{advertising_product_type}/edit",
 *     "delete-form" = "/admin/structure/advertising_product_type/{advertising_product_type}/delete",
 *     "collection" = "/admin/structure/advertising_product_type"
 *   }
 * )
 */
class AdvertisingProductType extends ConfigEntityBundleBase implements AdvertisingProductTypeInterface {
  /**
   * The Advertising Product type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Advertising Product type label.
   *
   * @var string
   */
  protected $label;

}
