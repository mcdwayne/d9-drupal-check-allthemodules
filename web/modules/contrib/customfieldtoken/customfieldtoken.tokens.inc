<?php

/**
 * @file
 * Contains customfieldtoken hooks.
 */

use Drupal\Core\Render\BubbleableMetadata;
/**
 * Implements hook_token_info().
 */
function customfieldtoken_token_info() {
  $types['custom_field_token'] = [
    'name' => t("custom field token"),
    'description' => t("custom tokens for fields"),
  ];
  // Site-wide global tokens.
  $query = db_select('custom_token', 'ct');
  $query->fields('ct', ['field_machine_name', 'max_trim_length', 'token_desc']);
  $result = $query->execute();
  foreach ($result as $value) {
    $custom_field_token[$value->field_machine_name] = [
      'name' => $value->field_machine_name,
      'description' => $value->token_desc,

    ];
  }
  return [
    'types' => $types,
    'tokens' => [
      'custom_field_token' => $custom_field_token,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function customfieldtoken_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $token_service = \Drupal::token();
  $replacements = [];

  if ($type == 'custom_field_token') {
    $node = $data['node'];
    $query = db_select('custom_token', 'ct');
    $query->fields('ct', ['field_machine_name', 'max_trim_length', 'token_desc']);
    $result = $query->execute();
    foreach ($result as $value) {
      $field_name = $value->field_machine_name;
      $field_length = $value->max_trim_length;
      foreach ($tokens as $original) {
        $replacements[$original] = get_node_summary($field_name, $field_length);
      }
    }

  }
  return $replacements;
}

/**
 * Implements function to get summary of token field selected.
 */
function get_node_summary($field_name, $field_length) {

  $node = \Drupal::routeMatch()->getParameter('node');
  $type = $node->getType();
  $summary = "";
  $text = strip_tags($node->$field_name->value);
  $text = (substr($text, 0, $field_length) . '...');
  return $text;
}
