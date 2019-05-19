<?php

namespace Drupal\webform_cart\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Webform cart item entity type entity.
 *
 * @ConfigEntityType(
 *   id = "webform_cart_item_type",
 *   label = @Translation("Webform cart item entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\webform_cart\WebformCartItemTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_cart\Form\WebformCartItemTypeForm",
 *       "edit" = "Drupal\webform_cart\Form\WebformCartItemTypeForm",
 *       "delete" = "Drupal\webform_cart\Form\WebformCartItemTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webform_cart\WebformCartItemTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "webform_cart_item_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "webform_cart_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/webformcart/webform_cart_item_type/{webform_cart_item_type}",
 *     "add-form" = "/admin/structure/webformcart/webform_cart_item_type/add",
 *     "edit-form" = "/admin/structure/webformcart/webform_cart_item_type/{webform_cart_item_type}/edit",
 *     "delete-form" = "/admin/structure/webformcart/webform_cart_item_type/{webform_cart_item_type}/delete",
 *     "collection" = "/admin/structure/webformcart/webform_cart_item_type"
 *   }
 * )
 */
class WebformCartItemType extends ConfigEntityBundleBase implements WebformCartItemTypeInterface {

  /**
   * The Webform cart item entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Webform cart item entity type label.
   *
   * @var string
   */
  protected $label;

}
