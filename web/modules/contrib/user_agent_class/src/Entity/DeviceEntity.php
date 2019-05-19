<?php

namespace Drupal\user_agent_class\Entity;

/**
 * Defines the Device entity.
 *
 * @ConfigEntityType(
 *   id = "device_entity",
 *   label = @Translation("Device"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\user_agent_class\DeviceEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\user_agent_class\Form\DeviceEntityForm",
 *       "edit" = "Drupal\user_agent_class\Form\DeviceEntityForm",
 *       "delete" = "Drupal\user_agent_class\Form\DeviceEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\user_agent_class\DeviceEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "device_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/device/{device_entity}",
 *     "add-form" = "/admin/config/system/device/add",
 *     "edit-form" = "/admin/config/system/device/{device_entity}/edit",
 *     "delete-form" = "/admin/config/system/device/{device_entity}/delete",
 *     "collection" = "/admin/config/system/device"
 *   }
 * )
 */
class DeviceEntity extends UserAgentEntity {

}
