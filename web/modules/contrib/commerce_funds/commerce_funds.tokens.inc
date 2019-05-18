<?php

/**
 * @file
 * Token hooks for commerce_funds module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function commerce_funds_token_info() {
  $balance_type = [
    'name' => t('Balance'),
    'description' => t('User balances.'),
    'needs-data' => 'user_balance',
  ];

  $balance = [];
  $balance['user'] = [
    'name' => t('Owner'),
    'description' => t('The user the balance is owned by.'),
  ];
  $balance['amount'] = [
    'name' => t('Amount'),
    'description' => t('The amount balance of the user.'),
  ];

  return [
    'types' => [
      'commerce_funds_balance' => $balance_type,
    ],
    'tokens' => [
      'commerce_funds_balance' => $balance,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function commerce_funds_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'commerce_funds_balance' && !empty($data['commerce_funds_balance'])) {
    $balance = $data['balance'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'user':
          $replacements[$original] = $balance->uid;
          break;

        case 'amount':
          $replacements[$original] = $balance->balance;
          break;
      }
    }
  }

  return $replacements;
}
