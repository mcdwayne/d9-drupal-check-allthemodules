<?php

/**
 * @file
 * Provides tokens for the workflow participants module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\workflow_participants\Tokens;

/**
 * Implements hook_token_info_alter().
 */
function workflow_participants_token_info_alter(&$info) {
  return \Drupal::service('class_resolver')
    ->getInstanceFromDefinition(Tokens::class)
    ->infoAlter($info);
}

/**
 * Implements hook_tokens().
 */
function workflow_participants_tokens($type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  return \Drupal::service('class_resolver')
    ->getInstanceFromDefinition(Tokens::class)
    ->getTokens($type, $tokens, $data, $options, $bubbleable_metadata);
}
