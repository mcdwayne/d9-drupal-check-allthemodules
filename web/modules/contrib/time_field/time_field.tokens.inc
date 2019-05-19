<?php

/**
 * @file
 * Token callbacks for the token module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\time_field\Plugin\Field\FieldType\TimeRangeType;
use Drupal\time_field\Plugin\Field\FieldType\TimeType;
use Drupal\time_field\Time;

/**
 * Implements hook_token_info().
 */
function time_field_token_info() {
  $info = [];

  // Time tokens.
  $info['types']['time'] = [
    'name' => t('Time'),
    'description' => t('Tokens related to time.'),
  ];
  $info['tokens']['time']['format'] = [
    'name' => t('Format'),
    'description' => t('Value of the time.'),
  ];

  // Time Range tokens.
  $info['types']['time_range'] = [
    'name' => t('Time Range'),
    'description' => t('Tokens related to time.'),
  ];
  $info['tokens']['time_range']['from'] = [
    'name' => t('From'),
    'description' => t('From value of the time.'),
  ];
  $info['tokens']['time_range']['to'] = [
    'name' => t('To'),
    'description' => t('To value of the time.'),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function time_field_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  /** @var \Drupal\Core\Field\FieldItemList $list */
  if (isset($data['field_name'])) {
    $list = $data[$data['field_name']];
    foreach ($list as $type) {
      if ($type instanceof TimeType) {
        foreach ($tokens as $name => $original) {
          // Ignore default value.
          if ($name === 'value') {
            $time = Time::createFromTimestamp($type->value);
            $replacements[$original] = $time->format('g:i a');
            continue;
          }
          $time = Time::createFromTimestamp($type->value);
          $replacements[$original] = $time->format($name);
        }
      }

      if ($type instanceof TimeRangeType) {
        foreach ($tokens as $name => $original) {
          // Ignore default value.
          if ($name === 'to:value' || $name === 'from:value') {
            continue;
          }

          if ($name === 'to') {
            $time = Time::createFromTimestamp($type->to);
            $replacements[$original] = $time->format('g:i a');
          }

          if ($name === 'from') {
            $time = Time::createFromTimestamp($type->from);
            $replacements[$original] = $time->format('g:i a');
          }

          if (strpos($name, 'to:') === 0) {
            $time = Time::createFromTimestamp($type->to);
            $replacements[$original] = $time->format(str_replace('to:', '', $name));
          }

          if (strpos($name, 'from:') === 0) {
            $time = Time::createFromTimestamp($type->from);
            $replacements[$original] = $time->format(str_replace('from:', '', $name));
          }
        }
      }
    }
  }
  return $replacements;
}
