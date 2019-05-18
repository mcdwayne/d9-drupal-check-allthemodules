<?php

/**
 * @file
 * Contains content_snippets tokens hooks.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function content_snippets_token_info() {
  $info['types']['content_snippets'] = [
    'name' => t('Content Snippets'),
    'description' => t("Custom token type for site-wide Content Snippets."),
  ];
  $snippets = \Drupal::config('content_snippets.items')->get();
  $info['tokens']['content_snippets'] = [];
  foreach ($snippets as $key => $value) {
    // Site-wide global token.
    $info['tokens']['content_snippets'][$key] = [
      'name' => $key,
      'description' => t('description'),
    ];
  }

  return $info;
}

/**
 * Implements hook_tokens().
 */
function content_snippets_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'content_snippets') {
    $snippets = \Drupal::config('content_snippets.content')->get();
    foreach ($tokens as $name => $original) {
      $replacements[$original] = $snippets[$name];
    }
  }
  return $replacements;
}
