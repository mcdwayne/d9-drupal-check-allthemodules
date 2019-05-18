<?php

/**
 * @file
 * Builds placeholder replacement maptiler tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\leaflet_maptiler_token\TokenOperations;

/**
 * Implements hook_token_info().
 */
function leaflet_maptiler_token_token_info() {
  return \Drupal::classResolver()
    ->getInstanceFromDefinition(TokenOperations::class)
    ->tokenInfo();
}

/**
 * Implements hook_tokens().
 */
function leaflet_maptiler_token_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  return \Drupal::classResolver()
    ->getInstanceFromDefinition(TokenOperations::class)
    ->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}
