<?php

namespace Drupal\widget_engine\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Widget type entity.
 *
 * @ConfigEntityType(
 *   id = "widget_type",
 *   label = @Translation("Widget type"),
 *   handlers = {
 *     "access" = "Drupal\widget_engine\WidgetTypeAccessControlHandler",
 *     "list_builder" = "Drupal\widget_engine\WidgetTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\widget_engine\Form\WidgetTypeForm",
 *       "edit" = "Drupal\widget_engine\Form\WidgetTypeForm",
 *       "delete" = "Drupal\widget_engine\Form\WidgetTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\widget_engine\WidgetTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "widget_type",
 *   admin_permission = "administer widget types",
 *   bundle_of = "widget",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/widget_type/{widget_type}",
 *     "add-form" = "/admin/structure/widget_type/add",
 *     "edit-form" = "/admin/structure/widget_type/{widget_type}/edit",
 *     "delete-form" = "/admin/structure/widget_type/{widget_type}/delete",
 *     "collection" = "/admin/structure/widget_type"
 *   }
 * )
 */
class WidgetType extends ConfigEntityBundleBase implements WidgetTypeInterface {

  /**
   * The Widget type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Widget type label.
   *
   * @var string
   */
  protected $label;

}
