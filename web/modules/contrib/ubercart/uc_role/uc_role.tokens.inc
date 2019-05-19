<?php

/**
 * @file
 * Token hooks for the uc_role module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function uc_role_token_info() {
  $type = [
    'name' => t('Role promotions'),
    'description' => t('Tokens related to purchased, temporary roles.'),
    'needs-data' => 'uc_role',
  ];

  $tokens['expiration'] = [
    'name' => t('Expiration'),
    'description' => t('The date the role will expire.'),
    'type' => 'date',
  ];
  $tokens['name'] = [
    'name' => t('Role'),
    'description' => t('The associated role name'),
  ];

  return [
    'types' => ['uc_role' => $type],
    'tokens' => ['uc_role' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function uc_role_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'uc_role' && !empty($data['uc_role'])) {
    $object = $data['uc_role'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'expiration':
          $replacements[$original] = \Drupal::service('date.formatter')->format($object->expiration, 'medium');
          break;

        case 'name':
          $replacements[$original] = _uc_role_get_name($object->rid);
          break;
      }
    }

    if ($expiration_tokens = $token_service->findWithPrefix($tokens, 'expiration')) {
      $replacements += $token_service->generate('date', $expiration_tokens, ['date' => $object->expiration], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
