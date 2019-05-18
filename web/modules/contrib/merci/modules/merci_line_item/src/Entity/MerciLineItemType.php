<?php

namespace Drupal\merci_line_item\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Merci Line Item type entity.
 *
 * @ConfigEntityType(
 *   id = "merci_line_item_type",
 *   label = @Translation("Merci Line Item type"),
 *   handlers = {
 *     "list_builder" = "Drupal\merci_line_item\MerciLineItemTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\merci_line_item\Form\MerciLineItemTypeForm",
 *       "edit" = "Drupal\merci_line_item\Form\MerciLineItemTypeForm",
 *       "delete" = "Drupal\merci_line_item\Form\MerciLineItemTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\merci_line_item\MerciLineItemTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "merci_line_item_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "merci_line_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/merci/merci_line_item_type/{merci_line_item_type}",
 *     "add-form" = "/admin/merci/merci_line_item_type/add",
 *     "edit-form" = "/admin/merci/merci_line_item_type/{merci_line_item_type}/edit",
 *     "delete-form" = "/admin/merci/merci_line_item_type/{merci_line_item_type}/delete",
 *     "collection" = "/admin/merci/merci_line_item_type",
 *     "auto-label" = "/admin/merci/merci_line_item_type/{merci_line_item_type}/auto-label"
 *   }
 * )
 */
class MerciLineItemType extends ConfigEntityBundleBase implements MerciLineItemTypeInterface {

  /**
   * The Merci Line Item type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Merci Line Item type label.
   *
   * @var string
   */
  protected $label;

}
