<?php

/**
 * @file
 * Japan postal code drush command.
 */

/**
 * Implements hook_drush_command().
 */
function japan_postal_code_drush_command() {
  $items = [];

  $items['japan-postal-code-update'] = [
    'callback' => 'japan_postal_code_drush_update',
    'description' => dt('Fetch and update the Japan postal code data.'),
    'aliases' => ['jpc-update'],
    'examples' => [
      'drush japan-postal-code-update' => dt('Fetch and update the Japan postal code data.'),
    ],
  ];

  return $items;
}

/**
 * Drush callback to fetch and update the Japan postal code.
 *
 * @see japan_postal_code_config_form_submit()
 */
function japan_postal_code_drush_update() {
  $count = _japan_postal_code_update_all_postal_code();

  if ($count === FALSE) {
    drush_print(t('Japan postal code database table update failed.'), 'error');
  }
  else {
    drush_print(t('Japan postal code database table is successfully updated. @count records inserted.', ['@count' => $count]));
  }
}
