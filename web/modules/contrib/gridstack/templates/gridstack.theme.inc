<?php

/**
 * @file
 * Hooks and preprocess functions for the GridStack module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\blazy\Blazy;
use Drupal\gridstack\GridStackDefault;

/**
 * Prepares variables for gridstack.html.twig templates.
 */
function template_preprocess_gridstack(&$variables) {
  $element = $variables['element'];
  foreach (GridStackDefault::themeProperties() as $key) {
    $variables[$key] = isset($element['#' . $key]) ? $element['#' . $key] : [];
  }

  $settings                = &$variables['settings'];
  $settings               += GridStackDefault::htmlSettings();
  $attributes              = &$variables['attributes'];
  $settings['id']          = $id = Blazy::getHtmlId('gridstack', $settings['id']);
  $settings['class_by_id'] = strpos($id, 'gridstack-') !== FALSE ? str_replace('gridstack-', 'gridstack--', $id) : '';
  $settings['enabled']     = $settings['use_js'] && empty($settings['ungridstack']);
  $custom_classes          = empty($attributes['class']) ? [] : $attributes['class'];
  $attributes['class']     = array_merge(['gridstack'], $custom_classes);
  $variables['wrapper']    = empty($settings['wrapper']) ? 'div' : $settings['wrapper'];
}

/**
 * Prepares variables for gridstack-box.html.twig templates.
 */
function template_preprocess_gridstack_box(&$variables) {
  foreach (['attributes', 'content_attributes', 'delta', 'item', 'settings'] as $key) {
    $default = $key == 'delta' ? 0 : [];
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : $default;
  }

  $item     = &$variables['item'];
  $settings = &$variables['settings'];

  // Boxes may have captions.
  foreach (['alt', 'category', 'data', 'link', 'title'] as $key) {
    $item['caption'][$key] = isset($item['caption']) && isset($item['caption'][$key]) ? $item['caption'][$key] : [];
  }

  $item['box']           = isset($item['box']) ? $item['box'] : [];
  $item['caption']       = array_filter($item['caption']);
  $settings['type']      = isset($settings['type']) ? $settings['type'] : '';
  $settings['use_stamp'] = $settings['type'] == 'stamp';

  $classes = empty($variables['attributes']['class']) ? [] : $variables['attributes']['class'];
  $variables['attributes']['class'] = array_merge(['gridstack__box', 'box'], $classes);
  $variables['caption_attributes'] = new Attribute();

  if (!empty($settings['debug']) || !empty($settings['_access_ipe'])) {
    $delta = empty($settings['root_delta']) ? $variables['delta'] : $settings['root_delta'];
    if (isset($settings['nested_delta'])) {
      $delta .= ':' . $settings['nested_delta'];
    }
    $variables['attributes']['data-delta'] = $delta;
  }
  if (!empty($settings['use_inner']) && !empty($settings['use_framework'])) {
    $settings['use_inner'] = !empty($item['box']);
  }

  $variables['wrapper'] = empty($settings['wrapper']) ? 'div' : $settings['wrapper'];
}
