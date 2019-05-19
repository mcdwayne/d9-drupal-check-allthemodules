<?php

/**
 * @file
 * Module to embed visualn drawings using tokens
 * Made on basis of token_embed_views module (8.x-1.0-alpha3 version)
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function visualn_token_token_info() {
  $info = array();
  $info['types']['visualn'] = array(
    'name' => t('VisualN'),
    'description' => ('Tokens to embed visualn drawings.'),
  );
  $info['tokens']['visualn']['embed'] = array(
    'name' => t('Embed visualn drawing'),
    'description' => t('Embed visualn drawing using tokens. The following values may be appended to the token: drawing:visualn-drawing-id'),
    // @todo: add an option to select fetcher field (and maybe even delta)
    //'description' => t('Embed visualn drawing using tokens. The following values may be appended to the token: drawing-entity-id:drawing-fetcher-field-name:delta'),
    // @todo: the 'dynamic' key is not describe on hook_token_info() api page
    'dynamic' => TRUE,
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function visualn_token_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();
  if ($type == 'visualn') {
    foreach ($tokens as $name => $original) {
      $args = explode(':', $name);
      $entity_id = $args[2];
      // @todo: maybe do additional checks (e.g. permissions)
      $entity = \Drupal::entityTypeManager()->getStorage('visualn_drawing')->load($entity_id);
      if (!empty($entity) && $entity->access('view')) {
        // @todo: add window_parameters (width and height) support to tokens
        //   also requires custom cache context type (see visualn_iframes.services.yml)
        $window_parameters = [];
        $entity->setWindowParameters($window_parameters);
        $drawing_markup = $entity->buildDrawing();
        $replacements[$original] = \Drupal::service('renderer')->render($drawing_markup);
      }
      elseif (!empty($entity) && !$entity->access('view')) {
        // @todo: show 'no view permission' drawing placeholder
        $replacements[$original] = '';
      }
      // @todo: add cache tags
    }
  }

  return $replacements;
}
