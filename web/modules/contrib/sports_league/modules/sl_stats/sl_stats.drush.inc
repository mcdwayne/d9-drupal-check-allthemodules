<?php

function drush_sl_stats($id) {

  $lock = \Drupal::lock();
  $computer = \Drupal::service('sl_stats.computer');

  if ($lock->acquire('sl_stats_autostats')) {
    $computer->compute($id);
    $lock->release('sl_stats_autostats');
  }
}

function drush_sl_stats_reset() {
  $computer = \Drupal::service('sl_stats.computer');
  $computer->sl_stats_reset();
}


function sl_stats_drush_command() {
  $items = array();

  $items['sl_stats'] = array(
    'description' => "Generates stats for players and seasons",
    'examples' => array(
      'drush sl_stats' => 'Generates stats for players and seasons.',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL
  );


  $items['sl_stats_reset'] = array(
    'description' => "Resets stats for players and seasons",
    'examples' => array(
      'drush sl_stats_reset' => 'Resets for players and seasons.',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL
  );

  return $items;
}

