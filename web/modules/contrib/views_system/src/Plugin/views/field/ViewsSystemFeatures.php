<?php

/**
 * @file
 * Contains \Drupal\views_system\Plugin\views\field\ViewsSystemFeatures.
 */


namespace Drupal\views_system\Plugin\views\field;

use Drupal\views\Plugin\views\field\PrerenderList;


/**
 * Field handler to display all features of a theme.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_system_features")
 */
class ViewsSystemFeatures extends PrerenderList {

  public function preRender(&$values) {
    $this->items = array();

    foreach ($values as $result) {

      $field = $this->getValue($result);
      if (!empty($field) && !isset($this->items[$field])) {

        foreach (unserialize($field) as $feature) {

          $this->items[$field][$feature]['name'] = $feature;
        }
      }
    }
  }

  function render_item($count, $item) {
    return $item['name'];
  }
}
