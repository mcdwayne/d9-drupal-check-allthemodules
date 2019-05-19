<?php

namespace Drupal\webform_cart\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Webform cart order entity type entity.
 *
 * @ConfigEntityType(
 *   id = "webform_cart_order_type",
 *   label = @Translation("Webform cart order entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\webform_cart\WebformCartOrderTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_cart\Form\WebformCartOrderTypeForm",
 *       "edit" = "Drupal\webform_cart\Form\WebformCartOrderTypeForm",
 *       "delete" = "Drupal\webform_cart\Form\WebformCartOrderTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webform_cart\WebformCartOrderTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "webform_cart_order_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "webform_cart_order",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/webformcart/webform_cart_order_type/{webform_cart_order_type}",
 *     "add-form" = "/admin/structure/webformcart/webform_cart_order_type/add",
 *     "edit-form" = "/admin/structure/webformcart/webform_cart_order_type/{webform_cart_order_type}/edit",
 *     "delete-form" = "/admin/structure/webformcart/webform_cart_order_type/{webform_cart_order_type}/delete",
 *     "collection" = "/admin/structure/webformcart/webform_cart_order_type"
 *   }
 * )
 */
class WebformCartOrderType extends ConfigEntityBundleBase implements WebformCartOrderTypeInterface {

  /**
   * The Webform cart order entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Webform cart order entity type label.
   *
   * @var string
   */
  protected $label;

}
