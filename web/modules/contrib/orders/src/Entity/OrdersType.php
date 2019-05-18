<?php

namespace Drupal\orders\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Orders type entity.
 *
 * @ConfigEntityType(
 *   id = "orders_type",
 *   label = @Translation("Orders type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\orders\OrdersTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\orders\Form\OrdersTypeForm",
 *       "edit" = "Drupal\orders\Form\OrdersTypeForm",
 *       "delete" = "Drupal\orders\Form\OrdersTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\orders\OrdersTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer orders entities",
 *   bundle_of = "orders",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/idcp/orders/type/{orders_type}",
 *     "add-form" = "/admin/idcp/orders/type/add",
 *     "edit-form" = "/admin/idcp/orders/type/{orders_type}/edit",
 *     "delete-form" = "/admin/idcp/orders/type/{orders_type}/delete",
 *     "collection" = "/admin/idcp/orders/type"
 *   }
 * )
 */
class OrdersType extends ConfigEntityBundleBase implements OrdersTypeInterface {

  /**
   * The Orders type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Orders type label.
   *
   * @var string
   */
  protected $label;

}
