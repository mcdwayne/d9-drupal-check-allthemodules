<?php

/**
 * @file
 * Contains the code to generate the mask_user_data drush commands.
 */

use Drupal\User\Entity\User;

/**
 * Implements hook_drush_command().
 */
function mask_user_data_drush_command() {
  $items = [];

  $items['mask_user_data'] = [
    'description' => dt('Masks user data in the database.'),
    'aliases' => ['mud'],
    'arguments' => [
      'uid' => 'User Id of the user to mask.',
    ],
    'examples' => [
      'drush mud' => 'Masks user data in the database.',
    ],
  ];

  return $items;
}

/**
 * Drush callback to mask user data.
 *
 * @param int $uid
 *   User ID. If none given then all users will be masked.
 */
function drush_mask_user_data($uid = NULL) {
  $enabled = Drupal::config('mask_user_data.settings')->get('enabled') ?: FALSE;
  if ($enabled) {
    drush_log(dt('Data masking is enabled.'), 'success');

    $already_masked = Drupal::config('mask_user_data.settings')->get('already_masked') ?: FALSE;
    if ($already_masked) {
      drush_log(dt('The user data has already been masked.'), 'success');
    }
    else {
      if (drush_confirm('Are you sure you want to mask user data?')) {
        $map_array = Drupal::config('mask_user_data.settings')->get('map_array') ?: NULL;
        if (empty($map_array) || !is_array($map_array)) {
          drush_log(dt('Invalid or empty map array. Please check the configuration.'), 'error');
        }
        else {
          if (!is_null($uid)) {
            if (User::load($uid)) {
              Drupal::service('mask_user_data.mask_user')->mask($uid, $map_array);
              drush_log(dt('User UID=' . $uid . ' was masked.'), 'success');
            }
            else {
              drush_log(dt('User UID=' . $uid . ' does not exists.'), 'error');
            }
          }
          else {
            mask_user_data_setup_batch();
          }
        }
      }
      else {
        drush_user_abort();
      }
    }
  }
  else {
    drush_log(dt('Data masking is disabled.'), 'success');
  }
}
