<?php

/**
 * @file
 * Hooks and preprocess functions for the GridStack UI module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for gridstack-ui-admin.html.twig templates.
 */
function template_preprocess_gridstack_ui_admin(&$variables) {
  if (!function_exists('template_preprocess_gridstack')) {
    module_load_include('inc', 'gridstack', 'templates/gridstack.theme');
  }

  template_preprocess_gridstack($variables);
  $element = $variables['element'];

  foreach (['content', 'preview', 'wrapper'] as $key) {
    $variables[$key . '_attributes'] = isset($element["#$key" . '_attributes']) ? $element["#$key" . '_attributes'] : [];
  }

  $settings = &$variables['settings'];
  $content_attributes = &$variables['content_attributes'];
  $preview_attributes = &$variables['preview_attributes'];
  $wrapper_attributes = &$variables['wrapper_attributes'];

  $settings['id'] = 'gridstack-' . $settings['breakpoint'];
  $content_attributes['id'] = $settings['id'];

  $variables['wrapper_attributes']['id'] = 'gridstack-wrapper-' . $settings['breakpoint'];
  $variables['wrapper_attributes']['data-breakpoint'] = $settings['breakpoint'];

  $variables['content_attributes'] = new Attribute($content_attributes);
  $variables['preview_attributes'] = new Attribute($preview_attributes);
  $variables['wrapper_attributes'] = new Attribute($wrapper_attributes);

  $variables['main_button_texts'] = [];

  $settings['is_main_preview'] = FALSE;
  if ($settings['breakpoint'] == $settings['icon_breakpoint']) {
    $settings['is_main_preview'] = TRUE;

    $variables['main_button_texts'] = [
      'save' => t('Update icon'),
      'add'  => t('Add grid'),
    ];
  }
}

/**
 * Prepares variables for gridstack-ui-dummy.html.twig templates.
 */
function template_preprocess_gridstack_ui_dummy(&$variables) {
  $element = $variables['element'];

  $all = ['delta', 'dummies', 'image_style', 'item', 'nested_grids', 'settings'];
  foreach ($all as $key) {
    $variables[$key] = isset($element["#$key"]) ? $element["#$key"] : [];
  }

  $variables['index']   = ((int) $variables['delta']) + 1;
  $variables['content'] = $element['#children'];

  foreach (['box', 'button_add', 'button_remove', 'nested'] as $key) {
    $variables[$key . '_attributes'] = new Attribute();
  }
}
