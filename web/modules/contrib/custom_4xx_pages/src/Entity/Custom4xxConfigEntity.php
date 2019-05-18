<?php

namespace Drupal\custom_4xx_pages\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Custom 4xx Configuration Item entity.
 *
 * @ConfigEntityType(
 *   id = "custom4xx_config_entity",
 *   label = @Translation("Custom 4xx Pages"),
 *   handlers = {
 *     "list_builder" = "Drupal\custom_4xx_pages\Custom4xxConfigEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\custom_4xx_pages\Form\Custom4xxConfigEntityForm",
 *       "edit" = "Drupal\custom_4xx_pages\Form\Custom4xxConfigEntityForm",
 *       "delete" = "Drupal\custom_4xx_pages\Form\Custom4xxConfigEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\custom_4xx_pages\Custom4xxConfigEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "custom4xx_config_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/custom4xx_config_entity/{custom4xx_config_entity}",
 *     "add-form" = "/admin/structure/custom4xx_config_entity/add",
 *     "edit-form" = "/admin/structure/custom4xx_config_entity/{custom4xx_config_entity}/edit",
 *     "delete-form" = "/admin/structure/custom4xx_config_entity/{custom4xx_config_entity}/delete",
 *     "collection" = "/admin/structure/custom4xx_config_entity"
 *   }
 * )
 */
class Custom4xxConfigEntity extends ConfigEntityBase implements Custom4xxConfigEntityInterface {

  /**
   * The Custom 4xx Configuration Item ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Custom 4xx Configuration Item label.
   *
   * @var string
   */
  protected $label;



}
