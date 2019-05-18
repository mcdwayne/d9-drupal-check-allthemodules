<?php

/**
 * @file
 * Hooks provided by the Login frequency module.
 */

/**
 * Override whether a login request should be tracked or not.
 *
 * @param bool $track
 *   Boolean indicating whether to track.
 * @param object $account
 *   The user object on which the operation is being performed.
 *
 * @see login_frequency_user_login()
 * @return bool $track
 */
function hook_login_frequency_track_login_alter(&$track, $account) {
  return FALSE;
}

/**
 * Modify custom data stored with the tracked login frequency.
 *
 * @param array $login_data
 *   Associative array of custom data that will be stored with the login.
 * @param object $account
 *   The user object on which the operation is about to be recorded.
 *
 * @see login_frequency_login_data()
 */
function hook_login_frequency_login_data_alter(array &$login_data, $account) {
  // Store a random number between 1 and 10 with the tracked login.
  $data['ip-address'] = '127.0.0.1';
}
