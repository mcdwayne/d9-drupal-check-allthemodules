<?php

/**
 * @file
 * Token related hook implementations.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function sender_token_info() {
  // Currently, Drupal does not allow 2 tokens of the same type in a single
  // text. So we create 2 token types for users.
  // See https://www.drupal.org/node/1920688.
  $types = [
    'sender-recipient' => [
      'name' => t('Recipient'),
      'description' => t('The recipient of the message.'),
      'needs-data' => 'sender_recipient',
    ],
  ];

  // Tokens will be copied from "user" in hook_token_info_alter().
  $tokens = [
    'sender-recipient' => [],
  ];

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_token_info_alter().
 */
function sender_token_info_alter(&$data) {
  // Copies the tokens for "user" to "recipient".
  $data['tokens']['sender-recipient'] = $data['tokens']['user'];
}

/**
 * Implements hook_tokens().
 */
function sender_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  // Delegates the replacement of the "recipient" token.
  if ($type == 'sender-recipient' && isset($data['sender_recipient'])) {
    $data['user'] = clone $data['sender_recipient'];
    $module_handler = \Drupal::moduleHandler();
    $hook_params = ['user', $tokens, $data, $options, $bubbleable_metadata];
    return $module_handler->invokeAll('tokens', $hook_params);
  }
}
