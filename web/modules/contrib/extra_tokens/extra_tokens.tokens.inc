<?php

/**
 * @file
 * Builds placeholder replacement tokens for taxonomy terms and vocabularies.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\taxonomy\Entity\Vocabulary;


/**
 * Implements hook_token_info().
 */
function extra_tokens_token_info() {
  $types['term'] = [
    'name' => t("Taxonomy terms"),
    'description' => t("Tokens related to taxonomy terms."),
    'needs-data' => 'term',
  ];

  $term['url-relative'] = [
    'name' => t("URL"),
    'description' => t("The URL of the taxonomy term."),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'term' => $term,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function extra_tokens_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'term' && !empty($data['term'])) {
    $term = $data['term'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'url-relative':
          $replacements[$original] = $term->url('canonical', ['absolute' => FALSE]);
          break;
      }
    }
  }
  return $replacements;
}
