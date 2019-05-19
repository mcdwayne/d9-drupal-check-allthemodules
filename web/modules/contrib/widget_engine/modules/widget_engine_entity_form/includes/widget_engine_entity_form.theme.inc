<?php

/**
 * @file
 * Preprocess for widget_engine_entity_form module.
 */

/**
 * Implements hook_theme().
 */
function widget_engine_entity_form_theme($existing, $type, $theme, $path) {
  $theme = [];
  $theme['widget__widget_form'] = array(
    'base hook' => 'widget',
    'template' => 'widget--widget-form',
  );

  return $theme;
}

/**
 * Implements hook_preprocess_HOOK().
 *
 * Preprocess to widget entity.
 */
function widget_engine_preprocess_widget(&$vars) {
  $view_mode = $vars['elements']['#view_mode'];
  $callback = '_widget_engine_preprocess_' . $view_mode;
  if (function_exists($callback)) {
    $callback($vars);
  }
}

/**
 * Preprocess to widget form, widget_form view mode.
 */
function _widget_engine_preprocess_widget_form(&$vars) {
  // Fetch Widget Entity Object.
  $widget = $vars['elements']['#widget'];
  // Build preview image.
  $data = $widget
    ->get('widget_preview')
    ->view(['label' => 'hidden']);
  $vars['image_preview'] = widget_engine_build_preview_image($data);

  // Build widget name.
  $vars['name'] = $widget
    ->get('name')
    ->getString();

  // Build widget id.
  $vars['id'] = $widget
    ->id();
}
