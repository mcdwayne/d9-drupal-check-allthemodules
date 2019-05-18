<?php

/**
 * @file
 * Builds placeholder replacement tokens for competition-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function competition_token_info() {
  $type = array(
    'name' => t('Competition Entries'),
    'description' => t('Tokens related to individual competition entries.'),
    'needs-data' => 'competition_entry',
  );

  $entry['ceid'] = array(
    'name' => t("Competition Entry ID"),
    'description' => t('The unique ID of the entry.'),
  );

  $entry['type'] = array(
    'name' => t("Competition Type"),
    'description' => t('The competition type of the entry.'),
  );

  $entry['cycle'] = array(
    'name' => t("Competition Cycle"),
    'description' => t('The competition cycle of the entry.'),
  );

  return array(
    'types' => array('competition_entry' => $type),
    'tokens' => array('competition_entry' => $entry),
  );
}

/**
 * Implements hook_tokens().
 */
function competition_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = array();

  if ($type == 'competition_entry' && !empty($data['competition_entry'])) {

    $entry = $data['competition_entry'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'type':
          $replacements[$original] = $entry->getType();
          break;

        case 'cycle':
          $replacements[$original] = $entry->getCycle();
          break;

        case 'ceid':
          $replacements[$original] = $entry->id();
          break;

      }
    }

  }

  return $replacements;
}
