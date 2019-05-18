<?php

/**
 * @file
 * Token related hook implementations.
 */

use Drupal\Core\Render\BubbleableMetadata;


/**
 * Implements hook_token_info().
 */
function node_notify_token_info() {

  $types['node-notify'] = [
    'name' => t('Node notify tokens'),
    'description' => t('Tokens used in node notify emails.'),
  ];
  $tokens['node-notify-title'] = [
    'name' => t('Node title'),
    'description' => t('Token for node title.'),
  ];
  $tokens['node-notify-url'] = [
    'name' => t('Node URL'),
    'description' => t('Tokens for node URL'),
  ];

  $tokens['node-notify-edit-url'] = [
    'name' => t('Node edit URL'),
    'description' => t('Tokens for node edit URL'),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'node-notify' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function node_notify_tokens($type, $tokens, $data, $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if($type == 'node-notify') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'node-notify-title':
          $replacements[$original] = $data['title'];
          break;
        case 'node-notify-url':
          $replacements[$original] = $data['url'];
          break;
        case 'node-notify-edit-url':
          $replacements[$original] = $data['url_edit'];
          break;
      }
    }
  }
  return $replacements;
}
