<?php

namespace Drupal\stacks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\stacks\WidgetExtendEntityTypeInterface;

/**
 * Defines the Widget Extend type entity.
 *
 * @ConfigEntityType(
 *   id = "widget_extend_type",
 *   label = @Translation("Widget Extend type"),
 *   handlers = {
 *     "list_builder" = "Drupal\stacks\WidgetExtendEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\stacks\Form\WidgetExtendEntityTypeForm",
 *       "edit" = "Drupal\stacks\Form\WidgetExtendEntityTypeForm",
 *       "delete" = "Drupal\stacks\Form\WidgetExtendEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\stacks\WidgetExtendEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "widget_extend_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "widget_extend",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/stacks/widget_extend_type/add",
 *     "edit-form" = "/admin/structure/stacks/widget_extend_type/{widget_extend_type}/edit",
 *     "delete-form" = "/admin/structure/stacks/widget_extend_type/{widget_extend_type}/delete",
 *     "collection" = "/admin/structure/stacks/widget_extend_type"
 *   }
 * )
 */
class WidgetExtendEntityType extends ConfigEntityBundleBase implements WidgetExtendEntityTypeInterface {
  /**
   * The Widget Extend type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Widget Extend type label.
   *
   * @var string
   */
  protected $label;

}
