<?php

/**
 * @file
 * packery.theme.inc
 */

use Drupal\Core\Template\Attribute;

/**
 * Template preprocess Packery variables.
 */
function template_preprocess_packery(&$variables) {
  // Load Packery settings.
  $group = packery_settings($variables['settings']);

  // Attach Packery library.
  $variables['#attached'] = array(
    'drupalSettings' => array(
      'packery' => array(
        $group['id'] => array(
          'settings' => $group['settings'],
          'extend' => $group['extend']
        )
      )
    ),
    'library' => array(
      'packery/local',
      'packery/settings'
    )
  );

  // Provide support for imagesLoaded plugin.
  if ($group['extend']['images_loaded']) {
    $variables['#attached']['library'][] = 'packery/imagesloaded.local';
  }
}

/**
 * Theme declaration for Packery.
 */
function theme_packery($variables) {
  $output = array(
    '#output' => array(
      'settings' => $variables['settings'],
      'items' => $variables['items']
    ),
    '#pre_render' => array('packery_pre_render_element')
  );

  return drupal_render($output);
}

/**
 * Pre render Packery output.
 */
function packery_pre_render_element($element) {
  $items = array();
  foreach ($element['#output']['items'] as $key => $item) {
    $items[] = array(
      '#theme' => 'packery_item',
      '#item' => $item['row'],
      '#attributes' => array(
        'class' => array('packery-item', 'item-' . $key)
      )
    );
  }

  // Build wrapper with Packery items.
  $build = packery_build($element['#output']['settings'], $items);

  // @todo, provide pre render alter.
  

  // Render the complete Packery element.
  $element['#children'] = drupal_render($build);

  return $element;
}

/**
 * Template preprocess Packery item variables.
 */
function template_preprocess_packery_item(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Returns the render array for Packery wrapper.
 */
function packery_build($group, $output) {
  return array(
    '#output' => $output,
    '#attributes' => array('class' => array($group, 'packery')),
    '#theme_wrappers' => array('packery_wrapper')
  );
}

/**
 * Template preprocess Packery wrapper variables.
 */
function template_preprocess_packery_wrapper(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}
