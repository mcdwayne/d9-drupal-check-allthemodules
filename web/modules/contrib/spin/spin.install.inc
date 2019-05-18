<?php

/**
 * @file
 * Install support functions.
 */

use Drupal\config;

/**
 * Insert the default profiles.
 */
function _spin_install_default_profiles() {
  $cfg = Drupal::config('spin.cfg');

  $key = [
    'name'  => 'default',
    'label' => 'Default',
    'type'  => 'spin',
  ];
  $fields = [
    'profile' => $cfg->get('spin_query'),
    'data'    => serialize([]),
  ];
  db_merge('spin')->key($key)->fields($fields)->execute();

  $key['type'] = 'slideshow';
  $fields['profile'] = $cfg->get('slideshow_options');

  db_merge('spin')->key($key)->fields($fields)->execute();
}
