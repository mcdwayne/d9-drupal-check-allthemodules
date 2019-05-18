<?php

/**
 * @file
 * Imagepin theme implementations.
 */

use Drupal\Core\Template\Attribute;

/**
 * Preprocess for showing imapepin widgets.
 *
 * @param array &$variables
 *   The variables array.
 */
function template_preprocess_imagepin_widgets(array &$variables) {
  $attach_id = $variables['attach_id'];
  $attributes = [
    'id' => 'widgets-' . $attach_id,
    'class' => ['imagepin-widgets'],
    'data-imagepin-attach-to' => $attach_id,
    'data-imagepin-breakpoint' => $variables['breakpoint'],
  ];
  $variables['attributes'] = new Attribute($attributes);
  $variables['items'] = [];

  foreach ($variables['widgets'] as $key => $widget) {
    $content = $widget['plugin']->viewContent($widget['value']);
    $pin_content = $widget['plugin']->viewPinContent($widget['value']);
    if (empty($content) || empty($pin_content)) {
      continue;
    }
    $item = [
      'attributes' => new Attribute(['class' => ['imagepin-widget'], 'data-imagepin-key' => $key, 'data-imagepin-overlay-class' => 'imagepin-widget imagepin-selected imagepin-overlay']),
      'content_attributes' => new Attribute(['class' => ['imagepin-widget-content']]),
      'content' => $content,
      'pin_attributes' => new Attribute(['class' => ['imagepin'], 'data-imagepin-key' => $key]),
      'pin_content' => $pin_content,
    ];
    if ($position = $widget['plugin']->getPosition($widget['value'])) {
      $item['pin_attributes']->setAttribute('data-position-default', \Drupal::service('serialization.json')->encode($position));
    }
    $variables['items'][$key] = $item;
  }
  if (!empty($variables['items'])) {
    $variables['#attached']['library'][] = 'imagepin/view';
  }
}
