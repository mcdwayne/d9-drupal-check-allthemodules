<?php

/**
 * @file
 * Token hooks for the uc_stock module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function uc_stock_token_info() {
  $type = [
    'name' => t('Stock level'),
    'description' => t('Tokens for the stock levels of products.'),
    'needs-data' => 'uc_stock',
  ];

  $tokens['level'] = [
    'name' => t('Level'),
    'description' => t('The current stock level.'),
  ];
  $tokens['model'] = [
    'name' => t('Model'),
    'description' => t('The model or SKU of the stock level.'),
  ];
  $tokens['threshold'] = [
    'name' => t('Threshold'),
    'description' => t('The threshold or warning limit of the stock level.'),
  ];

  return [
    'types' => ['uc_stock' => $type],
    'tokens' => ['uc_stock' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function uc_stock_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'uc_stock' && !empty($data['uc_stock'])) {
    $object = $data['uc_stock'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'level':
          $replacements[$original] = $object->stock;
          break;

        case 'model':
          $replacements[$original] = $object->sku;
          break;

        case 'threshold':
          $replacements[$original] = $object->threshold;
          break;
      }
    }
  }

  return $replacements;
}
