<?php
/**
 * @file
 * Contains flickity.theme.inc
 */

use Drupal\Core\Render\Element;

/**
 * Implements template_preprocess_flickity_entity_view().
 */
function template_preprocess_flickity_entity_view(array &$variables) {
  $elements = $variables['elements'];
  // Helpful $content variable for templates.
  foreach (Element::children($elements['#children']) as $key) {
    $variables['contents'][$key] = $elements['#children'][$key];
  }

  // Set Label and Display
  $variables['label_display'] = $elements['#label_display'];
  $variables['label_hidden'] = ($elements['#label_display'] == 'hidden');
  $variables['label'] = $elements['#title'];

  // Load Flickity settings.
  $group = flickity_settings($variables['elements']['#settings']);

  $variables['attributes'] = array(
    'class' => array(
      $group['id'],
      'flickity'
    )
  );

  // Attach Flickity library with settings.
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
