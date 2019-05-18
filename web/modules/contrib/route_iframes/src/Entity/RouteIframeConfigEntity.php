<?php

namespace Drupal\route_iframes\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Route Iframe Configuration entity.
 *
 * @ConfigEntityType(
 *   id = "route_iframe_config_entity",
 *   label = @Translation("Route Iframe Configuration"),
 *   handlers = {
 *     "list_builder" = "Drupal\route_iframes\RouteIframeConfigEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\route_iframes\Form\RouteIframeConfigEntityForm",
 *       "edit" = "Drupal\route_iframes\Form\RouteIframeConfigEntityForm",
 *       "delete" = "Drupal\route_iframes\Form\RouteIframeConfigEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\route_iframes\RouteIframeConfigEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "route_iframe_config_entity",
 *   admin_permission = "administer route iframe configuration entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "weight" = "weight",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/route_iframes/{route_iframe_config_entity}",
 *     "add-form" = "/admin/config/services/route_iframes/add",
 *     "edit-form" = "/admin/config/services/route_iframes/{route_iframe_config_entity}/edit",
 *     "delete-form" = "/admin/config/services/route_iframes/{route_iframe_config_entity}/delete",
 *     "collection" = "/admin/config/services/route_iframes"
 *   }
 * )
 */
class RouteIframeConfigEntity extends ConfigEntityBase implements RouteIframeConfigEntityInterface {

  /**
   * The Route Iframe Configuration ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Route Iframe Configuration label.
   *
   * @var string
   */
  protected $label;

  /**
   * The tab the iframe appears on.
   *
   * This is the tab within the secondary tabs.
   *
   * @var string
   */
  protected $tab;

  /**
   * The Route Iframe config scope type.
   *
   * The scope type describes how the scope should be used.
   *
   * @var string
   */
  protected $scope_type;

  /**
   * The Route Iframe config scope.
   *
   * The scope of content the configuration should affect.
   *
   * @var string
   */
  protected $scope;

  /**
   * The Route Iframe config.
   *
   * The path part of the url that should be used in the iframe.
   *
   * @var string
   */
  protected $config;

  /**
   * The position for determining overrides of configuration.
   *
   * @var int
   */
  protected $weight;

  /**
   * The height of the rendered iframe.
   *
   * @var int
   */
  protected $iframe_height;

}
