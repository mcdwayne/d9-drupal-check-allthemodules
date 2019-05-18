<?php

/**
 * @file
 * Builds placeholder replacement tokens for code-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function code_token_info() {
  $info['types']['code'] = [
    'name' => t('Code'),
  ];
  $info['tokens']['code']['rules'] = [
    'name' => t('Encoding rules'),
    'description' => t('"0" means digit, "A" means alphabet.'),
    'dynamic' => TRUE,
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function code_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'code' && !empty($data['entity_type_id'])) {
    $entity_type_id = $data['entity_type_id'];
    $field_name = $data['field_name'];
    $encoding_rules = $data['encoding_rules'];
    $bundle = $data['bundle'];
    $entity = $data[$entity_type_id];

    // The default replacements.
    foreach ($tokens as $name => $original) {
      $matches = [];
      preg_match('/\[code\:rules\:(.*)\]/', $original, $matches);
      $str = $matches[1];
      $replacements[$original] = $str;
    }

    $value = $encoding_rules;
    foreach ($tokens as $name => $original) {
      $value = str_replace($original, '%', $value);
    }
    $value = \Drupal::token()->replace($value, $data);
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $query = $storage->getQuery();
    if ($bundle_field = $entity->getEntityType()->getKey('bundle')) {
      $query->condition($bundle_field, $bundle);
    }
    $query->condition($field_name, $value, 'LIKE');
    $ids = $query->sort($field_name, 'DESC')
      ->range(0, 1)
      ->execute();
    if (!empty($ids)) {
      $entity = $storage->load(reset($ids));
      $str = $entity->$field_name->value;
      $pattern = preg_quote($value);
      $pattern = str_replace('%', '(.*)', $pattern);
      $matches = [];
      preg_match('/' . $pattern . '/', $str, $matches);
      $i = 1;
      foreach ($tokens as $name => $original) {
        if (!empty($matches[$i])) {
          $str = $matches[$i];
          $len = strlen($str);
          $str = str_pad(++$str, $len, "0", STR_PAD_LEFT);
          $replacements[$original] = $str;
        }
      }
    }
  }

  return $replacements;
}
