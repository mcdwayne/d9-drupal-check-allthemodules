<?php

/**
 * @file
 * Contains \Drupal\views_system\Plugin\views\field\ViewsSystemLibraries.
 */


namespace Drupal\views_system\Plugin\views\field;

use Drupal\views\Plugin\views\field\PrerenderList;


/**
 * Field handler to display all libraries of a theme.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_system_libraries")
 */
class ViewsSystemLibraries extends PrerenderList {

  public function preRender(&$values) {
    $this->items = array();

    foreach ($values as $result) {

      $field = $this->getValue($result);
      if (!empty($field) && !isset($this->items[$field])) {

        foreach (unserialize($field) as $library) {

          $this->items[$field][$library]['name'] = $library;
        }
      }
    }
  }

  function render_item($count, $item) {
    return $item['name'];
  }
}
