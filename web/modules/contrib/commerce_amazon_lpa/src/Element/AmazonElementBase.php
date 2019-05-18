<?php

namespace Drupal\commerce_amazon_lpa\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Amazon form element.
 */
abstract class AmazonElementBase extends RenderElement {

  /**
   * Attaches the Amazon Pay library.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   The render array.
   */
  public static function attachLibrary(array $element) {
    $element['#attached']['library'][] = 'commerce_amazon_lpa/amazon_pay';
    return $element;
  }

}
