<?php

/**
 * @file
 * Hooks and preprocess functions for the Mason module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;
use Drupal\blazy\Blazy;
use Drupal\mason\Entity\Mason;

/**
 * Prepares variables for mason.html.twig templates.
 */
function template_preprocess_mason(&$variables) {
  $element  = $variables['element'];
  $defaults = Mason::htmlSettings();
  $settings = isset($element['#settings']) ? array_merge($defaults, $element['#settings']) : $defaults;
  $json     = isset($element['#json']) ? $element['#json'] : '';

  // Prepare attributes, and add the configuration as JSON object.
  $id = Blazy::getHtmlId('mason', $settings['id']);
  $blazy = empty($settings['blazy_data']) ? '' : Json::encode($settings['blazy_data']);

  $attributes = new Attribute();
  $attributes->setAttribute('id', $id);
  $attributes->addClass('blazy');

  $attributes->setAttribute('data-mason', $json);
  $attributes->setAttribute('data-blazy', $blazy);

  if (!empty($settings['media_switch'])) {
    $switch = str_replace('_', '-', $settings['media_switch']);
    $attributes->setAttribute('data-' . $switch . '-gallery', TRUE);
  }

  // Pass settings and attributes to twig.
  $settings['class_by_id'] = strpos($id, 'mason-') !== FALSE ? str_replace('mason-', 'mason--', $id) : '';
  $variables['settings']   = $settings;
  $variables['attributes'] = $attributes;

  // Process individual item.
  $variables['items'] = [];
  foreach ($element['#items'] as $delta => $item) {
    $item_attributes = isset($item['attributes']) ? $item['attributes'] : [];
    $settings = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
    unset($item['settings']);

    $box = [
      '#theme'      => 'mason_box',
      '#item'       => $item,
      '#settings'   => $settings,
      '#delta'      => $delta,
      '#attributes' => $item_attributes,
    ];
    $variables['items'][$delta] = $box;
    unset($box);
  }
}

/**
 * Prepares variables for mason-box.html.twig templates.
 */
function template_preprocess_mason_box(&$variables) {
  foreach (['attributes', 'delta', 'item', 'settings'] as $key) {
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : [];
  }

  $attributes = &$variables['attributes'];
  $content_attributes = &$variables['content_attributes'];

  // Boxes may have captions.
  foreach (['data', 'link', 'title'] as $key) {
    $variables['item']['caption'][$key] = isset($variables['item']['caption'][$key]) ? $variables['item']['caption'][$key] : [];
  }

  $item     = &$variables['item'];
  $settings = $variables['settings'];

  $item['caption'] = array_filter($item['caption']);

  $variables['fillers'] = !empty($settings['fillers']) && ($variables['delta'] > $settings['fillers']);
  $variables['content_attributes'] = new Attribute($content_attributes);
}
