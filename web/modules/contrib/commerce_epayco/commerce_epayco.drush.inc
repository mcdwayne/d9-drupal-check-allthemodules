<?php

/**
 * @file
 * Contains the code to generate drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function commerce_epayco_drush_command() {
  $items = [];

  $items['ep-check-pending-payments'] = [
    'description' => 'Check for pending payments.',
    'options' => [
      'remote_id' => 'Restrict checking to a single or comma-separated value of remote IDs.',
    ],
    'drupal dependencies' => ['commerce_epayco'],
    'aliases' => ['ep-cpp'],
  ];

  return $items;
}

/**
 * Callback function for the checking pending payments function.
 */
function drush_commerce_epayco_ep_check_pending_payments() {
  $remote_id = drush_get_option('remote_id');
  $conf = [];
  if ($remote_id) {
    $remote_id = array_filter(explode(',', preg_replace('/\s/', '', $remote_id)));
    $conf['properties']['remote_id'] = $remote_id;
  }

  $processed = commerce_epayco_check_pending_orders($conf);
  drush_print(dt('Total processed payments: @total', ['@total' => $processed['counters']['_total']]));
}
