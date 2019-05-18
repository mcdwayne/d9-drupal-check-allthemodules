<?php

/**
 * @file
 * Describes & provides token(s) for Consumers Token module.
 */

/**
 * Implements hook_token_info().
 */
function consumers_token_token_info() {
  $info = [];

  $info['types']['consumers'] = [
    'name' => t('Consumers'),
    'description' => t('Consumers tokens')
  ];

  $info['tokens']['consumers']['current-name'] = [
    'name' => t('Consumer name'),
    'description' => t('The token returns the name of the consumer which requested the data.'),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function consumers_token_tokens($type, $tokens) {
  $replacements = [];

  if ($type != 'consumers') {
    return $replacements;
  }

  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'current-name':
        /* @var $consumer \Drupal\Core\Entity\EntityInterface */
        // Grab consumer from the current request.
        $consumer = \Drupal::service('consumer.negotiator')
          ->negotiateFromRequest();
        $replacements[$original] = !empty($consumer) ? $consumer->label() : '';
        break;
    }
  }

  return $replacements;
}
