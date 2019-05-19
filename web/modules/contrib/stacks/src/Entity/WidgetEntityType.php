<?php

namespace Drupal\stacks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\stacks\WidgetEntityTypeInterface;

/**
 * Defines the Widget Entity type entity.
 *
 * @ConfigEntityType(
 *   id = "widget_entity_type",
 *   label = @Translation("Widget Entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\stacks\WidgetEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\stacks\Form\WidgetEntityTypeForm",
 *       "edit" = "Drupal\stacks\Form\WidgetEntityTypeForm",
 *       "delete" = "Drupal\stacks\Form\WidgetEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\stacks\WidgetEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "widget_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "widget_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "plugin" = "plugin"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/stacks/widget_entity_type/add",
 *     "edit-form" = "/admin/structure/stacks/widget_entity_type/{widget_entity_type}/edit",
 *     "delete-form" = "/admin/structure/stacks/widget_entity_type/{widget_entity_type}/delete",
 *     "collection" = "/admin/structure/stacks/widget_entity_type"
 *   }
 * )
 */
class WidgetEntityType extends ConfigEntityBundleBase implements WidgetEntityTypeInterface {
  /**
   * The Widget Entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Widget Entity type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Widget Entity type plugin.
   *
   * @var string
   */
  protected $plugin;

  /**
   * Get plugin.
   *
   * @return string
   */
  public function getPlugin() {
    return $this->plugin;
  }

}
