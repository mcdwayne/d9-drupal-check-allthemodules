<?php

/**
 * @file
 * Consent iFrame theme inc file.
 */

use Drupal\Component\Utility\Crypt;

/**
 * Preprocess callback for an OIL iFrame.
 *
 * @param array $variables
 *   The preprocess variables.
 */
function template_preprocess_oil_iframe(array &$variables) {
  $variables['#attached']['library'][] = 'consent/layer';
  $variables['#attached']['html_head'][] = [
    ['#type' => 'html_tag', '#tag' => 'title', '#value' => t('Consent')],
    'title',
  ];
  $variables['#attached']['html_head'][] = [
    [
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'robots',
        'content' => 'noindex,follow',
      ],
    ],
    'robots',
  ];

  /** @var \Drupal\consent\Oil\OilConfigBuilderInterface $config_builder */
  $config_builder = \Drupal::service('consent.oil_config');
  $variables['oil_config'] = $config_builder->buildConfigTag($variables['config']);
  // Create placeholder strings, just like
  // the system module does for the html template.
  $types = [
    'styles' => 'css',
    'scripts' => 'js',
    'scripts_bottom' => 'js-bottom',
    'head' => 'head',
  ];
  $variables['placeholder_token'] = Crypt::randomBytesBase64(55);
  foreach ($types as $type => $placeholder_name) {
    $placeholder = '<' . $placeholder_name . '-placeholder token="' . $variables['placeholder_token'] . '">';
    $variables['#attached']['html_response_attachment_placeholders'][$type] = $placeholder;
  }
}
