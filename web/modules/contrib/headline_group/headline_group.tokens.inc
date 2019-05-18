<?php

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function headline_group_token_info() {
  $info = [];

  $info['types'] = [
    'headline_group' => [
      'name' => t('Headline group'),
      'description' => t('Tokens for headline group components'),
    ]
  ];

  $info['tokens'] = [
    'headline_group' => [
      'headline' => [
        'name' => t('Headline group headline'),
        'description' => t('The primary headline field'),
      ],
      'superhead' => [
        'name' => t('Headline group superheadline'),
        'description' => t('The superheading'),
      ],
      'subhead' => [
        'name' => t('Headline group subheadline'),
        'description' => t('The subheading'),
      ],
    ],
  ];
  
  return $info;
}

/**
 * Implements hook_tokens().
 */
function headline_group_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'headline_group') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'headline':
          $replacements[$original] = $data['headline'];
          break;
        case 'superhead':
          $replacements[$original] = $data['superhead'];
          break;
        case 'subhead':
          $replacements[$original] = $data['subhead'];
          break;
      }
    }
  }   

  return $replacements;
}