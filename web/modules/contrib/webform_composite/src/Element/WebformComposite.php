<?php

namespace Drupal\webform_composite\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides an composite webform element.
 *
 * Derived from WebformCompositeBase in order to override getCompositeElements
 * to allow storage of configured element data in config instead of requiring
 * elements in source code.
 *
 * @FormElement("webform_composite")
 */
class WebformComposite extends WebformCompositeBase {

  /**
   * Get a renderable array of webform elements.
   *
   * @return array
   *   A renderable array of webform elements, containing the base properties
   *   for the composite's webform elements.
   */
  public static function getCompositeElements(array $element) {
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    if ($element["#type"] === "webform_composite") {
      $element["#type"] = $element["#webform_composite"];
    }
    $instance = $element_manager->getElementInstance($element);
    $sub_elem = $instance->getCompositeElements();
    return $sub_elem;
  }

}
