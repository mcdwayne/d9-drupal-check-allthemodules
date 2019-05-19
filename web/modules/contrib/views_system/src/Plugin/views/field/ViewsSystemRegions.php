<?php

/**
 * @file
 * Contains \Drupal\views_system\Plugin\views\field\ViewsSystemRegions.
 */


namespace Drupal\views_system\Plugin\views\field;

use Drupal\views\Plugin\views\field\PrerenderList;


/**
 * Field handler to display all regions of a theme.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_system_regions")
 */
class ViewsSystemRegions extends PrerenderList {

  public function preRender(&$values) {
    $this->items = array();

    foreach ($values as $result) {

      $field = $this->getValue($result);
      if (!empty($field) && !isset($this->items[$field])) {

        foreach (unserialize($field) as $name => $label) {

          $this->items[$field][$name]['label'] = $label;
          $this->items[$field][$name]['name'] = $name;
        }
      }
    }
  }

  function render_item($count, $item) {
    return $item['label'];
  }

  protected function documentSelfTokens(&$tokens) {
    $tokens['{{ ' . $this->options['id'] . '__label' . ' }}'] = $this->t('The human readable name of the region.');
    $tokens['{{ ' . $this->options['id'] . '__name' . ' }}'] = $this->t('The machine-name of the region.');
  }

  protected function addSelfTokens(&$tokens, $item) {
    if (!empty($item['name'])) {
      $tokens['{{ ' . $this->options['id'] . '__label' . ' }}'] = $item['label'];
      $tokens['{{ ' . $this->options['id'] . '__name' . ' }}'] = $item['name'];
    }
  }
}
