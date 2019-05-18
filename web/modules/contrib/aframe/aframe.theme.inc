<?php

/**
 * @file
 */

use Drupal\Core\Template\Attribute;

/**
 * Implements hook_preprocess_HOOK() for aframe-tag.html.twig.
 */
function aframe_preprocess_aframe_tag(&$variables) {
  $element = $variables['element'];
  $variables['children'] = $element['#children'];
  $variables['tag'] = $element['#tag'];
}

/**
 * Prepares variables for views aframe rows templates.
 *
 * Default template: views-view-aframe.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 */
function template_preprocess_views_view_aframe(&$variables) {
  $view = $variables['view'];
  $rows = $variables['rows'];
  $style = $view->style_plugin;
  $options = $style->options;

  $variables['default_row_class'] = !empty($options['default_row_class']);
  foreach ($rows as $id => $row) {
    $variables['rows'][$id] = [];
    $variables['rows'][$id]['content'] = $row;
    $variables['rows'][$id]['attributes'] = new Attribute();
    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $variables['rows'][$id]['attributes']->addClass($row_class);
    }

    /** @var \Drupal\aframe\AFrameComponentPluginManager $component_manager */
    $component_manager = \Drupal::service('plugin.manager.aframe.component');
    $components = $component_manager->getDefinitions();
    foreach ($components as $component) {
      if (isset($options['aframe_components'][$component['id']])) {
        /** @var \Drupal\aframe\AFrameComponentPluginInterface $component_instance */
        $component_instance = $component_manager->createInstance($component['id'], [
          'settings' => [
            $component['id'] => $options['aframe_components'][$component['id']],
          ],
        ]);
        $value = $component_instance->getValue();
        if ($value) {
          $value = $view->style_plugin->tokenizeValue($value, $id);
          $variables['rows'][$id]['attributes']->setAttribute($component['id'], $value);
        }
      }
    }
  }
}
