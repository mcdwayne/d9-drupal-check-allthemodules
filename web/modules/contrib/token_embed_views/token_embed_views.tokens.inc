<?php
/**
 * @file
 * Module to embed views using tokens
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function token_embed_views_token_info() {
  $info = array();
  $info['types']['views'] = array(
    'name' => t('Views'),
    'description' => ('Tokens to embed views.'),
  );
  $info['tokens']['views']['embed'] = array(
    'name' => t('Embed views'),
    'description' => t('Embed views using tokens. The following values may be appended to the token: view-name:display-id:arg1/arg2/arg3'),
    'dynamic' => TRUE,
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function token_embed_views_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();
  if ($type == 'views') {

    foreach ($tokens as $name => $original) {
      $args = explode(':', $name);
      // Remove the first element out as its not a required parameter for the view.
      array_shift($args);
      $view = call_user_func_array('views_embed_view', $args);
      $replacements[$original] = \Drupal::service('renderer')->render($view);
    }
  }

  return $replacements;
}
