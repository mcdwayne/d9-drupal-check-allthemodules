<?php

namespace Drupal\bills\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Bills type entity.
 *
 * @ConfigEntityType(
 *   id = "bills_type",
 *   label = @Translation("Bills type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bills\BillsTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bills\Form\BillsTypeForm",
 *       "edit" = "Drupal\bills\Form\BillsTypeForm",
 *       "delete" = "Drupal\bills\Form\BillsTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\bills\BillsTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer bills entities",
 *   bundle_of = "bills",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/idcp/bills/type/{bills_type}",
 *     "add-form" = "/admin/idcp/bills/type/add",
 *     "edit-form" = "/admin/idcp/bills/type/{bills_type}/edit",
 *     "delete-form" = "/admin/idcp/bills/type/{bills_type}/delete",
 *     "collection" = "/admin/idcp/bills/type"
 *   }
 * )
 */
class BillsType extends ConfigEntityBundleBase implements BillsTypeInterface {

  /**
   * The Bills type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Bills type label.
   *
   * @var string
   */
  protected $label;

}
