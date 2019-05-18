<?php

/**
 * @file
 * flickity.theme.inc
 */

use Drupal\Core\Template\Attribute;

/**
 * Template preprocess Flickity variables.
 */
function template_preprocess_flickity(&$variables) {
  // Load Flickity settings.
  $group = flickity_settings($variables['settings']);

  // Attach Flickity library.
  $variables['#attached'] = array(
    'drupalSettings' => array(
      'flickity' => array(
        $group['id'] => array(
          'settings' => $group['settings']
        )
      )
    ),
    'library' => array(
      'flickity/local',
      'flickity/settings'
    )
  );
}

/**
 * Theme declaration for Flickity.
 */
function theme_flickity($variables) {
  $output = array(
    '#output' => array(
      'settings' => $variables['settings'],
      'items' => $variables['items']
    ),
    '#pre_render' => array('flickity_pre_render_element')
  );

  return drupal_render($output);
}

/**
 * Pre render Flickity output.
 */
function flickity_pre_render_element($element) {
  $items = array();
  foreach ($element['#output']['items'] as $key => $item) {
    $items[] = array(
      '#theme' => 'flickity_item',
      '#item' => $item['row'],
      '#attributes' => array(
        'class' => array('gallery-cell', 'item-' . $key)
      )
    );
  }

  // Build wrapper with Flickity items.
  $build = flickity_build($element['#output']['settings'], $items);

  // Provide pre render alter.
  \Drupal::moduleHandler()->alter('pre_render', $build);

  // Render the complete Flickity element.
  $element['#children'] = drupal_render($build);

  return $element;
}

/**
 * Template preprocess Flickity item variables.
 */
function template_preprocess_flickity_item(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Returns the render array for Flickity wrapper.
 */
function flickity_build($group, $output) {
  return array(
    '#output' => $output,
    '#attributes' => array('class' => array($group, 'flickity')),
    '#theme_wrappers' => array('flickity_wrapper')
  );
}

/**
 * Template preprocess Flickity wrapper variables.
 */
function template_preprocess_flickity_wrapper(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}
