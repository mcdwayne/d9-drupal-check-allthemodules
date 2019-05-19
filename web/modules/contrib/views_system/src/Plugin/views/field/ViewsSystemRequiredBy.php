<?php

/**
 * @file
 * Contains \Drupal\views_system\Plugin\views\field\ViewsSystemRequiredBy.
 */


namespace Drupal\views_system\Plugin\views\field;

use Drupal\views\Plugin\views\field\PrerenderList;


/**
 * Field handler to display all other items that depends on this item.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_system_required_by")
 */
class ViewsSystemRequiredBy extends PrerenderList {

  public function preRender(&$values) {
    $this->items = array();

    foreach ($values as $result) {

      $field = $this->getValue($result);
      if (!empty($field) && !isset($this->items[$field])) {

        foreach (unserialize($field) as $name => $value) {

          $label = db_query('SELECT label FROM {views_system} WHERE name = :name', array(':name' => $name))
            ->fetchField();

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
    $tokens['{{ ' . $this->options['id'] . '__label' . ' }}'] = $this->t('The human readable name of the item.');
    $tokens['{{ ' . $this->options['id'] . '__name' . ' }}'] = $this->t('The machine-name of the item.');
  }

  protected function addSelfTokens(&$tokens, $item) {
    if (!empty($item['name'])) {
      $tokens['{{ ' . $this->options['id'] . '__label' . ' }}'] = $item['label'];
      $tokens['{{ ' . $this->options['id'] . '__name' . ' }}'] = $item['name'];
    }
  }
}
