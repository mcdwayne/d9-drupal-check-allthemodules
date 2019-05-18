<?php

/**
 * @file
 * Hooks and preprocess functions for the Blazy module.
 */

use Drupal\Component\Serialization\Json;
use Drupal\blazy\Blazy;

/**
 * Prepares variables for elevatezoomplus.html.twig templates.
 */
function template_preprocess_elevatezoomplus(array &$variables) {
  // Supported themes: Blazy (field|item_list), slick_wrapper, gridstack.
  $element = $variables['element'];
  $variables['settings'] = [];
  foreach (['blazy', 'build', 'context', 'settings'] as $key) {
    if (isset($element['#' . $key])) {
      $settings = isset($element['#' . $key]['settings']) ? $element['#' . $key]['settings'] : [];
      $variables['settings'] = ($key == 'build' || $key == 'context') ? $settings : $element['#' . $key];
      break;
    }
  }

  // Reset theme attributes as we are a wrapper/ container here.
  $settings = $variables['settings'];
  $json = elevatezoomplus()->getOptions($settings);
  $variables['attributes'] = isset($element['#container_attributes']) ? $element['#container_attributes'] : [];
  $variables['attributes']['data-elevatezoomplus'] = Json::encode($json);
  $variables['content_attributes'] = ['data-blazy' => ''];

  // Checks if we have Slick asNavFor, or regular Slick, Blazy Grid, etc.
  // Only provides static stage if not using Slick asNavFor.
  $variables['stage'] = empty($settings['nav']) ? elevatezoomplus_build($settings) : [];
  $variables['content'] = isset($element['#children']) ? $element['#children'] : [];
}

/**
 * Build the main gallery image/ stage if not using Slick asNavFor.
 */
function elevatezoomplus_build(array $settings = []) {
  $uri   = $settings['first_uri'];
  $item  = isset($settings['first_item']) ? $settings['first_item'] : new \stdClass();
  $url   = empty($settings['box_style']) ? file_url_transform_relative(file_create_url($uri)) : blazy()->entityLoad($settings['box_style'])->buildUrl($uri);
  $style = empty($settings['thumbnail_style']) ? '' : $settings['thumbnail_style'];
  $param = ['image_style' => $style, 'lazy' => 'blazy', 'uri' => $uri];
  $id    = Blazy::getHtmlId('elevatezoomplus');
  $zoom  = ['data-zoom-image' => $url];
  $attrs = ['class' => ['elevatezoomplus elevatezoomplus--stage'], 'id' => $id] + $zoom;
  $build = ['item' => $item, 'settings' => $param, 'item_attributes' => $attrs];

  return [
    '#type'     => 'inline_template',
    '#template' => '{{ stage }}',
    '#context'  => ['stage' => blazy()->getBlazy($build)],
    '#attached' => blazy()->attach($param + $settings),
  ];
}
