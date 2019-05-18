<?php

use Drupal\Core\Template\Attribute;

/**
 * Preprocess variables for flexfield.html.twig
 */
function template_preprocess_flexfield(&$variables) {
  // Set the item attributes
  foreach ($variables['items'] as &$item) {
    // Attributes are optional so we check if it's set first and process
    // appropriately
    if (isset($item['attributes'])) {
      $item['attributes'] = new Attribute($item['attributes']);
    }
    else {
      $item['attributes'] = new Attribute();
    }
  }
}
