<?php
/**
 * @file
 * ipquery drush commands
 */

/**
 * Implements hook_drush_command().
 */
function ipquery_drush_command() {
  $items = [];
  $items['ipquery-import'] = [
    'description' => dt('Download and import the latest ipquery file'),
    'aliases' => ['ipqi'],
    'options' => [
      'ipv4' => dt('Only process IPv4 files'),
      'ipv6' => dt('Only process IPv6 files'),
    ],
  ];
  return $items;
}

/**
 * Drush command to download a new file.
 */
function drush_ipquery_import() {
  /** @var \Drupal\ipquery\Ip2LocationDownloadService $download */
  $download = \Drupal::service('ipquery.ip2location.download');
  $download->process([
    'drush' => TRUE,
    'ipv4only' => drush_get_option('ipv4'),
    'ipv6only' => drush_get_option('ipv6'),
  ]);
}
