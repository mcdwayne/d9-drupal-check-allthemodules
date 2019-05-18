<?php
/**
 * @file Contains the code to generate drupal_reset drush command.
 */

/**
 * Implements hook_drush_command().
 */
function drupal_reset_drush_command() {
  $items = [];
  $items['site-reset'] = [
    'description' => dt('Reset the site installation, start fresh.'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
    'arguments' => [
      'option' => 'all, files or database',
    ],
    'aliases' => ['sr'],
  ];
  return $items;
}

/**
 * Call back function drush_custom_drush_command_say_hello()
 * The call back function name in the  following format
 *   drush_{module_name}_{item_id_for_command}()
 */
function drush_drupal_reset_site_reset($option = 'all') {
  $dropDatabase = \Drupal::service('drupal_reset.drop_database');

  if (!$dropDatabase->validateIsSupported()) {
    drush_set_error('DRUPAL_RESET_DATABASE_CONFIGURATION_NOT_SUPPORTED', 'Your database configuration is not supported by the Drupal Reset module.
There must be one database (no master/slave) and the table prefix must be set to a string (not an array); use the empty string if you do not want a prefix.
See your settings.php file.');
  }

  $deleteFiles = \Drupal::service('drupal_reset.delete_files');

  switch ($option) {
    case 'all':
      if (!drush_confirm(dt('You are about to drop all database tables and delete all the files for this site. Do you want to continue?'))) {
        return drush_user_abort();
      }
      $dropDatabase->dropdatabase();
      drush_log('Dropping the database', 'ok');
      $deleteFiles->deletefiles();
      drush_log('Deleting the files', 'ok');
      drush_log('Visit /install.php from browser to reinstall drupal', 'ok');
      break;

    case 'files':
      if (!drush_confirm(dt('You are about to delete all the files for this site. Do you want to continue?'))) {
        return drush_user_abort();
      }
      $deleteFiles->deletefiles();
      drush_log('Deleting the files', 'ok');
      break;

    case 'database':
      if (!drush_confirm(dt('You are about to drop all database tables for this site. Do you want to continue?'))) {
        return drush_user_abort();
      }
      $dropDatabase->dropdatabase();
      drush_log('Dropping the database', 'ok');
      break;
  }
}