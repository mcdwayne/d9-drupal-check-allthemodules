<?php

/**
 * @file
 * Builds placeholder replacement tokens for dcat-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function dcat_token_info() {
  $type = [
    'name' => t('DCAT'),
    'description' => t('Tokens related to DCAT.'),
  ];

  $dcat['dataset-total'] = [
    'name' => t("Total"),
    'description' => t('The total number of datasets.'),
  ];

  $dcat['dataset-per-theme'] = [
    'name' => t("Total"),
    'description' => t('The number of datasets per theme, given is the term id.'),
    'dynamic' => TRUE,
  ];

  return [
    'types' => ['DCAT' => $type],
    'tokens' => ['DCAT' => $dcat],
  ];
}

/**
 * Implements hook_tokens().
 */
function dcat_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacement = [];

  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'dataset-total':
        $query = \Drupal::entityQuery('dcat_dataset')->count();
        $replacement[$original] = $query->execute();
    }
  }

  // [DCAT:dataset-per-theme:*] dynamic token.
  if ($per_term_tokens = \Drupal::token()->findWithPrefix($tokens, 'dataset-per-theme')) {
    foreach ($per_term_tokens as $tid => $original) {
      if (is_int($tid)) {
        $query = \Drupal::entityQuery('dcat_dataset')->count();
        $query->condition('theme', $tid);
        $replacement[$original] = $query->execute();
        $bubbleable_metadata->addCacheTags(['dcat_dataset_list']);
      }
    }
  }

  return $replacement;
}
