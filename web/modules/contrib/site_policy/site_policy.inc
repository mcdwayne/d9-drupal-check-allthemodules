<?php

/**
 * @file
 * Private functions for best practice module.
 */

/**
 * Determine if user 1 should be disabled.
 */
function _site_policy_should_user_1_be_disabled() {
  $config = \Drupal::config('site_policy.settings');
  $time_limit = $config->get('site_policy_user_1_active_time');
  if (empty($time_limit) || !is_numeric($time_limit) || $time_limit < 1) {
    return FALSE;
  }

  $account = \Drupal\user\Entity\User::load(1);
  return (!empty($account->get('status')) && $account->get('access') < (time() - $time_limit));
}

/**
 * Disable user 1 account if the last access time is outside maximum.
 */
function _site_policy_disable_user_1() {
  if (_site_policy_should_user_1_be_disabled()) {
    $account = \Drupal\user\Entity\User::load(1);
    $account
      ->set('status', 0)
      ->save();
    \Drupal::logger('site_policy')->notice('User 1 account was disabled');
  }
}

/**
 * Scramble user 1 account password
 */
function _site_policy_scramble_user_1_password() {
  $config = \Drupal::config('site_policy.settings');
  $time_limit = $config->get('site_policy_user_1_pw_scramble');

  if (empty($time_limit) || !is_numeric($time_limit) || $time_limit < 1) {
    return;
  }

  if (_site_policy_get_next_scramble_user_1_password_time() > time()) {
    return;
  }

  $account = \Drupal\user\Entity\User::load(1);
  $new_passsword = user_password(50);
  $account
    ->setPassword($new_passsword)
    ->save();
  \Drupal::state()->set('site_policy.site_policy_last_user1_password_scramble', time());
  \Drupal::logger('site_policy')->notice('User 1 password was scrambled');
}

/**
 * Get the next time the uset 1 password will be scrambled.
 * @return null
 */
function _site_policy_get_next_scramble_user_1_password_time() {
  $config = \Drupal::config('site_policy.settings');
  $time_limit = $config->get('site_policy_user_1_pw_scramble');
  $last_user1_password_scramble = \Drupal::state()->get('site_policy.site_policy_last_user1_password_scramble');

  if (empty($last_user1_password_scramble)) {
    $last_user1_password_scramble = time();
    \Drupal::state()->set('site_policy.site_policy_last_user1_password_scramble', $last_user1_password_scramble);
  }

  return $last_user1_password_scramble + $time_limit;
}
