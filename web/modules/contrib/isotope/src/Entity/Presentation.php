<?php
/**
 * @file
 * Contains \Drupal\isotope\Entity\Presentation.
 */

namespace Drupal\isotope\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\isotope\Controller\IsotopeController;
use Drupal\isotope\PresentationInterface;

/**
 * Defines a Presentation configuration entity.
 *
 * @ConfigEntityType(
 *   id = "presentation",
 *   label = @Translation("Isotope reusable presentation configuration"),
 *   handlers = {
 *     "access" = "Drupal\isotope\PresentationAccessController",
 *     "list_builder" = "Drupal\isotope\Controller\PresentationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\isotope\Form\PresentationAddForm",
 *       "edit" = "Drupal\isotope\Form\PresentationEditForm",
 *       "delete" = "Drupal\isotope\Form\PresentationDeleteForm",
 *     }
 *   },
 *   config_prefix = "presentation",
 *   admin_permission = "configure isotope",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/media/isotope/{presentation}",
 *     "delete-form" = "/admin/config/media/isotope/{presentation}/delete",
 *   }
 * )
 */
class Presentation extends ConfigEntityBase implements PresentationInterface {

  /**
   * The machine-readable ID for the configurable.
   */
  public $id;

  /**
   * The human-readable label for the configurable.
   */
  public $label;

  /**
   * The isotope layoutMode property. String.
   */
  public $layout_mode;

  /**
   * A serialized array of plugin names.
   */
  public $plugins;

  /**
   * The isotope transitionDuration property. Use CSS time format.
   */
  public $transition_duration;

  /**
   * Should filters be represented in URL?. Boolean.
   */
  public $url_filters;

  /**
   * The isotope isFitWidth property. Boolean.
   */
  public $is_fit_width;

  /**
   * The isotope isHorizontal property. Boolean.
   */
  public $is_horizontal;

  /**
   * Selector of element to be stamped.
   */
  public $stamp;

  /**
   * The isotope horizontalAlignment property. Decimal between 0 and 1.
   */
  public $horizontal_alignment;

  /**
   * The isotope verticalAlignment property. Decimal between 0 and 1.
   */
  public $vertical_alignment;

  /**
   * The isotope isOriginLeft property. Boolean.
   */
  public $is_origin_left;

  /**
   * Convert a presentation into a JSON representation.
   *
   * Style as per http://isotope.metafizzy.co/#initialize-in-html.
   */
  public function toJSON(array $additional_attributes = []) {
    // Properties that should be passed directly to isotope.
    $allowed_attributes = [
      'layoutMode',
      'transitionDuration',
      'urlFilters',
      'isFitWidth',
      'isHorizontal',
      'stamp',
      'horizontalAlignment',
      'verticalAlignment',
      'isOriginLeft',
    ];

    // Default attributes.
    $attributes = [
        'columnWidth' => '.js-isotope-grid-sizer',
        'itemSelector' => '.js-isotope-element',
        'gutter' => '.js-isotope-gutter-sizer',
      ] + $additional_attributes;

    foreach ($this as $key => $value) {
      if (!empty($value)) {
        $key = IsotopeController::snakeToCamel($key);
        if (in_array($key, $allowed_attributes)) {
          $attributes[$key] = $value;
        }
      }
    }
    return Json::encode($attributes);
  }

}
