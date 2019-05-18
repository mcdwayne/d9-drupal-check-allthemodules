<?php

/**
 * @file
 * Provides drush commands for pcb.
 */

use Drupal\Core\Cache\Cache;

/**
 * Implements hook_drush_command().
 */
function pcb_drush_command() {
  $commands = [];

  $commands['permanent-cache-bin-flush'] = [
    'description' => 'Flush permanent cache bin.',
    'aliases'     => ['pcbf'],
    'arguments'     => [
      'bin' => 'Bin to flush cache of.',
    ],
    'required-arguments' => TRUE,
    'examples'    => [
      'drush pcbf stock' => 'Flush stock bin cache.',
    ],
  ];

  return ($commands);
}

/**
 * Implements drush permanent cache bin flush command.
 */
function drush_pcb_permanent_cache_bin_flush($bin) {
  try {
    \Drupal::service('cache.' . $bin)->deleteAllPermanent();
    drush_print(dt('Deleted all cache for @bin.', ['@bin' => $bin]));
  }
  catch (\Exception $e) {
    drush_set_error($e->getMessage());
  }
}

/**
 * Implements drush permanent cache bin flush command.
 */
function drush_pcb_permanent_cache_bin_list() {
  $bins = Cache::getBins();

  foreach ($bins AS $bin => $object) {
    if (method_exists($object, 'deleteAllPermanent')) {
      drush_print($bin);
    }
  }
}
