<?php

namespace Drupal\advanced_cors\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Route configuration entity.
 *
 * @ConfigEntityType(
 *   id = "route_config",
 *   label = @Translation("Route configuration"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\advanced_cors\RouteConfigEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\advanced_cors\Form\RouteConfigEntityForm",
 *       "edit" = "Drupal\advanced_cors\Form\RouteConfigEntityForm",
 *       "delete" = "Drupal\advanced_cors\Form\RouteConfigEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\advanced_cors\RouteConfigEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "route_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/advanced_cors/{route_config}",
 *     "add-form" = "/admin/config/services/advanced_cors/add",
 *     "edit-form" = "/admin/config/services/advanced_cors/{route_config}/edit",
 *     "delete-form" = "/admin/config/services/advanced_cors/{route_config}/delete",
 *     "collection" = "/admin/config/services/advanced_cors"
 *   }
 * )
 */
class RouteConfigEntity extends ConfigEntityBase implements RouteConfigEntityInterface {

  /**
   * The Route configuration ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Route configuration label.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight of this config.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getPatterns() {
    return array_map('trim', explode(PHP_EOL, $this->get('patterns')));
  }

}
