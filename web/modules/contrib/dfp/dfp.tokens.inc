<?php

/**
 * @file
 * Builds placeholder replacement tokens for DFP Ad tag data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function dfp_token_info() {
  $type = [
    'name' => t('DFP Ad Tags'),
    'description' => t('Tokens related to a given DFP ad tag.'),
    'needs-data' => 'dfp_tag',
  ];

  $tag['slot'] = [
    'name' => t('Slot Name'),
    'description' => t("The name of the ad slot defined by this tag."),
  ];
  $tag['network_id'] = [
    'name' => t("Network ID"),
    'description' => t("The unique ID provided by Google."),
  ];

  return [
    'types' => ['dfp_tag' => $type],
    'tokens' => ['dfp_tag' => $tag],
  ];
}

/**
 * Implements hook_tokens().
 */
function dfp_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'dfp_tag') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'slot':
          if (!empty($data['dfp_tag'])) {
            $replacements[$original] = $data['dfp_tag']->getSlot();
          }
          break;

        case 'network_id':
          $config = \Drupal::config('dfp.settings');
          $replacements[$original] = $config->get('network_id');
          $bubbleable_metadata->addCacheableDependency($config);
          break;
      }
    }
  }

  return $replacements;
}
