<?php

/**
 * @file
 * Integration of Private Taxonomy with Tokens.
 */

use Drupal\Component\Utility\Html;

/**
 * Implements hook_token_info().
 */
function private_taxonomy_token_info() {
  $types['term'] = [];

  $private_taxonomy['term_owner_name'] = [
    'name' => t("Private Term Owner Name"),
    'description' => t("The {user}.name of the term owner."),
  ];
  $private_taxonomy['term_owner_uid'] = [
    'name' => t("Private Term Owner UID"),
    'description' => t("The {user}.uid of the term owner."),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'term' => $private_taxonomy,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function private_taxonomy_tokens($type, $tokens, array $data = [], array $options = []) {
  $token_service = \Drupal::token();

  $replacements = [];
  $sanitize = !empty($options['sanitize']);

  if ($type == 'term' && !empty($data['term'])) {
    $term = $data['term'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'term_owner_name':
          $tid = $term->id();
          $sql = \Drupal::database()->select('user_term', 'user_term');
          $sql->join('users_field_data', 'u', 'u.uid = user_term.uid');
          $name = $sql->condition('user_term.tid', $tid)
            ->fields('u', ['name'])
            ->execute()
            ->fetchField();
          $replacements[$original]
            = $sanitize ? Html::escape($name) : $name;
          break;

        case 'term_owner_uid':
          $tid = $term->id();
          $uid = \Drupal::database()->select('user_term', 'user_term')
            ->condition('user_term.tid', $tid)
            ->fields('user_term', 'uid')
            ->execute()
            ->fetchField();
          $replacements[$original] = $uid;
          break;
      }
    }
  }

  return $replacements;
}
