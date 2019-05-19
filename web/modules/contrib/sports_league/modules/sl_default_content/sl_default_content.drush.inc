<?php

use Drupal\sl_default_content\SLDefaultContentGenerator;

function drush_sl_default_content() {
  $contentGenerator = new SLDefaultContentGenerator();
  $contentGenerator->mainGenerator();
}


function sl_default_content_drush_command() {
  $items = array();

  $items['sl_default_content'] = array(
    'description' => "Generates stats for players and seasons",
    'examples' => array(
      'drush sl_default_content' => 'Generates stats for players and seasons.',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL
  );


  $items['sl_default_content_reset'] = array(
    'description' => "Resets stats for players and seasons",
    'examples' => array(
      'drush sl_default_content_reset' => 'Resets for players and seasons.',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL
  );

  return $items;
}

