<?php

/**
 * @file
 * Token for Dlike.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function dlike_token_info() {

  $types = array();
  $tokens = array();

  // Flag tokens.
  $types['flagcount'] = array(
    'name' => t('Flags count'),
    'description' => t('Tokens related to flag count (dlike) module.'),
  );
  $tokens['flagcount']['count'] = array(
    'name' => t('Count'),
    'description' => t('Number of times the flag has been flagged.'),
    'needs-data' => 'flag',
  );

  return array(
    'types' => $types,
    'tokens' => $tokens,
  );
}

 /**
  * Implements hook_tokens().
  */
function dlike_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  if ($type == 'flagcount') {
    foreach ($tokens as $name => $original) {
      // Find the desired token by name
      switch ($name) {
        case 'count':

          if (isset($data['flag_id']) && isset($data['entity_id'])) {

            // Query the db for the count associated with this entity.
            $query = \Drupal::database()->select('flag_counts', 'fc');
            $query->fields('fc', ['count']);
            $query->condition('fc.flag_id', $data['flag_id']);
            $query->condition('fc.entity_id', $data['entity_id']);
            $count_db = $query->execute()->fetchAssoc();

            if (!isset($count_db['count'])) {
              $count_db['count'] = 0;
            }

            $replacements[$original] = $count_db['count'];
          }

          break;
      }
    }
  }

  // Return the replacements.
  return $replacements;
}
