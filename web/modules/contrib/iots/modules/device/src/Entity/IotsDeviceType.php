<?php

namespace Drupal\iots_device\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Device type entity.
 *
 * @ConfigEntityType(
 *   id = "iots_device_type",
 *   label = @Translation("Device type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\iots_device\IotsDeviceTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\iots_device\Form\IotsDeviceTypeForm",
 *       "edit" = "Drupal\iots_device\Form\IotsDeviceTypeForm",
 *       "delete" = "Drupal\iots_device\Form\IotsDeviceTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\iots_device\IotsDeviceTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "iots_device_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "iots_device",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/iots_device_type/{iots_device_type}",
 *     "add-form" = "/admin/structure/iots_device_type/add",
 *     "edit-form" = "/admin/structure/iots_device_type/{iots_device_type}/edit",
 *     "delete-form" = "/admin/structure/iots_device_type/{iots_device_type}/delete",
 *     "collection" = "/admin/structure/iots_device_type"
 *   }
 * )
 */
class IotsDeviceType extends ConfigEntityBundleBase implements IotsDeviceTypeInterface {

  /**
   * The Device type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Device type label.
   *
   * @var string
   */
  protected $label;

}
