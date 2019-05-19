<?php

namespace Drupal\simple_styleguide\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Styleguide pattern entity.
 *
 * @ConfigEntityType(
 *   id = "styleguide_pattern",
 *   label = @Translation("Styleguide pattern"),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_styleguide\StyleguidePatternListBuilder",
 *     "form" = {
 *       "add" = "Drupal\simple_styleguide\Form\StyleguidePatternForm",
 *       "edit" = "Drupal\simple_styleguide\Form\StyleguidePatternForm",
 *       "delete" = "Drupal\simple_styleguide\Form\StyleguidePatternDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\simple_styleguide\StyleguidePatternHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "styleguide_pattern",
 *   admin_permission = "administer style guide",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/styleguide/patterns/{styleguide_pattern}",
 *     "add-form" = "/admin/config/styleguide/patterns/add",
 *     "edit-form" = "/admin/config/styleguide/patterns/{styleguide_pattern}/edit",
 *     "delete-form" = "/admin/config/styleguide/patterns/{styleguide_pattern}/delete",
 *     "collection" = "/admin/config/styleguide/patterns"
 *   }
 * )
 */
class StyleguidePattern extends ConfigEntityBase implements StyleguidePatternInterface {

  /**
   * The Styleguide pattern ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The styleguide pattern label.
   *
   * @var string
   */
  protected $label;

  /**
   * The styleguide pattern.
   *
   * @var string
   */
  public $pattern;

}
