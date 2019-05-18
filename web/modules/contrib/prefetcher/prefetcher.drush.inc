<?php
/**
 * @file Contains debug commans for teaser.
 */

/**
 * Implements hook_drush_command().
 */
function prefetcher_drush_command() {
  $items = [];
  $items['prefetcher-run'] = [
    'description' => 'Runs prefetcher',
    'arguments' => [
      'entity_type' => 'The entity type to act on.',
      'entity_id' => 'The entity id to act on.',
    ],
    'options' => [
      'block-size' => 'Block size for each request pool',
      'limit' => 'Limit',
      'not-crawled' => 'Only process uris which have never been crawled before',
      'expiry' => 'Include uris to prefetch with the given maximum time in seconds until expiry. When not given, the configuration value will be used.',
      'silent' => 'Suppress status messages in the output stream.',
    ],
    'drupal dependencies' => ['prefetcher'],
    'examples' => [
      'drush prefetcher run --limit="100"' => 'Prefetch 100 uris.',
    ],
  ];
  $items['prefetcher-reset'] = [
    'description' => "Resets the prefetcher queue so that all uri items are being crawled as soon as possible.",
    'drupal dependencies' => ['prefetcher'],
  ];
  return $items;
}

/**
 * Resets the prefetcher queue.
 */
function drush_prefetcher_reset() {
  /** @var \Drupal\prefetcher\Service\Prefetcher $prefetcher */
  $prefetcher = \Drupal::service('prefetcher');
  $prefetcher->reset();
}

/**
 * Runs prefetcher.
 */
function drush_prefetcher_run() {
  $options = [
    'block-size' => (int) drush_get_option('block-size'),
    'limit' => drush_get_option('limit'),
    'not-crawled' => drush_get_option('not-crawled') ? TRUE : FALSE,
    'expiry' => drush_get_option('expiry'),
    'silent' => (bool) drush_get_option('silent'),
  ];
  /** @var \Drupal\prefetcher\Service\Prefetcher $prefetcher */
  $prefetcher = \Drupal::service('prefetcher');
  $prefetcher->run($options);
}
