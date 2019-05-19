<?php

/**
 * @file
 * Contains \Drupal\views_system\Plugin\views\field\ViewsSystemDependencies.
 */


namespace Drupal\views_system\Plugin\views\field;

use Drupal\views\Plugin\views\field\PrerenderList;
use Drupal\Core\Extension\ModuleHandler;


/**
 * Field handler to display all dependencies of a module.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_system_dependencies")
 */
class ViewsSystemDependencies extends PrerenderList {

  public function preRender(&$values) {
    $this->items = array();

    foreach ($values as $result) {

      $field = $this->getValue($result);
      if (!empty($field) && !isset($this->items[$field])) {

        foreach (unserialize($field) as $module) {

          $dependency = ModuleHandler::parseDependency($module);
          $label = db_query('SELECT label FROM {views_system} WHERE name = :name', array(':name' => $dependency['name']))
            ->fetchField();

          $this->items[$field][$module]['label'] = $label;
          $this->items[$field][$module]['name'] = $dependency['name'];
          $this->items[$field][$module]['version'] = isset($dependency['original_version']) ? trim($dependency['original_version']) : '';
        }
      }
    }
  }

  function render_item($count, $item) {
    return !empty($item['version']) ? $item['label'] . ' ' . $item['version'] : $item['label'];
  }

  protected function documentSelfTokens(&$tokens) {
    $tokens['{{ ' . $this->options['id'] . '__label' . ' }}'] = $this->t('The human readable name of the module.');
    $tokens['{{ ' . $this->options['id'] . '__name' . ' }}'] = $this->t('The machine-name of the module.');
    $tokens['{{ ' . $this->options['id'] . '__version' . ' }}'] = $this->t('The version of the module.');
  }

  protected function addSelfTokens(&$tokens, $item) {
    if (!empty($item['name'])) {
      $tokens['{{ ' . $this->options['id'] . '__label' . ' }}'] = $item['label'];
      $tokens['{{ ' . $this->options['id'] . '__name' . ' }}'] = $item['name'];
      $tokens['{{ ' . $this->options['id'] . '__version' . ' }}'] = $item['version'];
    }
  }
}
