<?php

/**
 * @file
 * Builds placeholder replacement tokens for User bundle-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info_alter().
 */
function user_bundle_token_info_alter(&$data) {
  // Add bundle tokens to the user token type.
  $data['tokens']['user']['type'] = [
    'name' => t('Account type'),
  ];
  $data['tokens']['user']['type-name'] = [
    'name' => t('Account type name'),
    'description' => t('The human-readable name of the user account type.'),
  ];
}

/**
 * Implements hook_tokens().
 */
function user_bundle_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'user' && !empty($data['user'])) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $data['user'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'type':
          $replacements[$original] = $account->getType();
          break;

        case 'type-name':
          $type_name = user_bundle_get_user_type_label($account);
          $replacements[$original] = $type_name;
          break;
      }
    }
  }

  return $replacements;
}
