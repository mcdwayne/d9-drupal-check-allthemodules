<?php

namespace Drupal\css_background\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the CssBackground type entity.
 *
 * @ConfigEntityType(
 *   id = "css_background_type",
 *   label = @Translation("CSS background type"),
 *   handlers = {
 *     "list_builder" = "Drupal\css_background\CssBackgroundEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\css_background\Form\CssBackgroundEntityTypeForm",
 *       "edit" = "Drupal\css_background\Form\CssBackgroundEntityTypeForm",
 *       "delete" = "Drupal\css_background\Form\CssBackgroundEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\css_background\CssBackgroundEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "css_background_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "css_background",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/css-background-type/{css_background_type}",
 *     "add-form" = "/admin/structure/css-background-type/add",
 *     "edit-form" = "/admin/structure/css-background-type/{css_background_type}/edit",
 *     "delete-form" = "/admin/structure/css-background-type/{css_background_type}/delete",
 *     "collection" = "/admin/structure/css-background-type"
 *   }
 * )
 */
class CssBackgroundEntityType extends ConfigEntityBundleBase implements CssBackgroundEntityTypeInterface {

  /**
   * The CSS background type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The CSS background type label.
   *
   * @var string
   */
  protected $label;

}
