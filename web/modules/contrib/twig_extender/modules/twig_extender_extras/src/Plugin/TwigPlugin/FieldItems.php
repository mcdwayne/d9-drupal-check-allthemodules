<?php

namespace Drupal\twig_extender_extras\Plugin\TwigPlugin;

use Drupal\Core\Render\Element;
use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * Twig plugin for loop render children.
 *
 * @TwigPlugin(
 *   id = "twig_extender_element_children",
 *   label = @Translation("Identifies the children of an element array, optionally sorted by weight."),
 *   type = "filter",
 *   name = "children",
 *   function = "children"
 * )
 */
class FieldItems extends TwigPluginBase {

  /**
   * Identifies the children of an element array, optionally sorted by weight.
   *
   * The children of a element array are those key/value pairs whose key does
   * not start with a '#'. See drupal_render() for details.
   *
   * @param array $elements
   *   The element array whose children are to be identified. Passed by
   *   reference.
   * @param bool $sort
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return array
   *   The filtered array to loop over.
   *
   * @throws \Exception
   */
  public function children(array $elements, $sort = FALSE) {
    if (gettype($elements) !== 'array') {
      throw new \Exception('Could not convert object to array.');
    }

    $ids = Element::children($elements, $sort);

    return array_intersect_key($elements, array_flip($ids));
  }

}
