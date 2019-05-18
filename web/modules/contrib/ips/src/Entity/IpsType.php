<?php

namespace Drupal\ips\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Ips type entity.
 *
 * @ConfigEntityType(
 *   id = "ips_type",
 *   label = @Translation("Ips type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ips\IpsTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ips\Form\IpsTypeForm",
 *       "edit" = "Drupal\ips\Form\IpsTypeForm",
 *       "delete" = "Drupal\ips\Form\IpsTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ips\IpsTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "ips",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/idcp/ips/type/{ips_type}",
 *     "add-form" = "/admin/idcp/ips/type/add",
 *     "edit-form" = "/admin/idcp/ips/type/{ips_type}/edit",
 *     "delete-form" = "/admin/idcp/ips/type/{ips_type}/delete",
 *     "collection" = "/admin/idcp/ips/type"
 *   }
 * )
 */
class IpsType extends ConfigEntityBundleBase implements IpsTypeInterface {

  /**
   * The Ips type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Ips type label.
   *
   * @var string
   */
  protected $label;

}
