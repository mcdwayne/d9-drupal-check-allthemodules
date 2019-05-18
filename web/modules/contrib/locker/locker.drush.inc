<?php

/**
 * @file
 * Locker drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function locker_drush_command() {

  $commands['lock'] = [
    'description' => 'Lock site.',
    'aliases' => ['lock'],
    'arguments' => [
      'passphrase'    => "Enter your desired passphrase to unlock the site.",
      'username'      => "Enter your desired username to unlock the site.",
      'password'      => "Enter your desired password to unlock the site.",
    ],
    'examples' => [
      'drush lock u username password' => 'Lock your Drupal site with username = username & password = password.',
      'drush lock passphrase' => 'Lock your Drupal site with passphrase = passphrase.',
    ],
  ];

  $commands['unlock'] = [
    'description' => 'Unlock your Drupal site.',
    'aliases' => ['unlock'],
    'examples' => [
      'drush unlock' => 'Unlock your Drupal site.',
    ],
  ];
  return $commands;
}

/**
 * Drush command logic.
 *
 * A drush_[MODULE_NAME]_[COMMAND_NAME]().
 *
 * @param string $passphrase
 *   Option (lock or unlock)
 * @param string $user
 *   Username or Passphrase (optional)
 * @param string $pass
 *   Pass_word (optional)
 */
function drush_locker_lock($passphrase = '', $user = '', $pass = '') {
  $config = \Drupal::service('config.factory')->getEditable('locker.settings');
  if (empty($passphrase)) {
    $options = [
      '1' => t('Passphrase'),
      '2' => t('Username/password'),
    ];
    $choiced_option = drush_choice($options, t('
    Locker module - usage:

    drush lock u username password  - to lock site with username and password
    drush lock passphrase       - to lock site only with passphrase
    drush unlock            - to unlock site

Please choose a option.'));

    if ($choiced_option == 0) {
      \Drupal::logger('locker')->error(t('Error! Process cancelled.'));
      drush_print(t('Error! Process cancelled.'));
    }
    elseif ($choiced_option == 1) {
      $passphrase = drush_prompt(t('Please choose a passphrase'));
    }
    elseif ($choiced_option == 2) {
      $passphrase = 'u';
      $user = drush_prompt(t('Please choose a username'));
      $pass = drush_prompt(t('Please choose a password'));
    }
  }

  if (isset($passphrase)) {
    if (empty($passphrase)) {
      \Drupal::logger('locker')->error(t('Error! No passphrase found.'));
      drush_print(t('Error! No passphrase found.'));
    }
    elseif ($passphrase == 'u') {
      $passmd5 = md5($pass);
      $config
        ->set('locker_user', $user)
        ->set('locker_password', $passmd5);
      $locker_access_options = 'user_pass';
    }
    else {
      $config
        ->set('locker_passphrase', md5($passphrase));
      $locker_access_options = 'passphrase';
    }
  }

  if (!empty($locker_access_options)) {
    $config->set('locker_access_options', $locker_access_options);
    $config->set('locker_custom_url', 'unlock.html');
    $config->set('locker_site_locked', 'yes')->save();
    \Drupal::logger('locker')->notice(t('Successfully locked.'));
    drush_print(t('Successfully locked.'));
    $query = \Drupal::database()->delete('sessions');
    $query->execute();
  }
  drupal_flush_all_caches();
}

/**
 * Drush unlock method.
 */
function drush_locker_unlock() {
  $config = \Drupal::service('config.factory')->getEditable('locker.settings');
  $status = $config->get('locker_site_locked');
  $config->set('locker_site_locked', '')->save();
  $config->delete();
  unset($_SESSION['locker_unlocked']);
  \Drupal::logger('locker')->notice(t('Site successfully unlocked.'));
  if ($status) {
    drush_print(t('Site successfully unlocked.'));
  }
  else {
    drush_print(t('Site was already unlocked.'));
  }
  drupal_flush_all_caches();
}
