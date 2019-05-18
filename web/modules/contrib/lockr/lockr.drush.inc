<?php

/**
 * @file
 * Hooks and callbacks for drush.
 */

use Lockr\Exception\LockrClientException;
use Lockr\Exception\LockrServerException;

/**
 * Implements hook_drush_command().
 */
function lockr_drush_command() {
  $info['lockdown'] = array(
    'description' => 'Patch modules to work with key.',
    'aliases' => array('ld'),
    'core' => array('8'),
    'scope' => 'site',
  );

  $info['lockr-register'] = array(
    'description' => 'Register a site for Lockr.',
    'aliases' => array('lr'),
    'core' => array('8'),
    'scope' => 'site',
    'arguments' => array(
      'email' => 'The email to register with.',
    ),
    'options' => array(
      'password' => 'The password to match given email (if applicable).',
    ),
  );

  return $info;
}

/**
 * Registers the site with lockr.
 */
function drush_lockr_register($email) {
  $name = variable_get('site_name');
  $client = \Drupal::service('lockr.client');
  $password = drush_get_option('password');

  try {
    $client->register($email, $password, $name);
  }
  catch (LockrClientException $e) {
    if (
      $e->title === 'Missing header value' &&
      $e->description === 'The Auth header is required.'
    ) {
      $msg = 'Email is already registered, please provide a password.';
    }
    elseif ($e->title === 'Partner mismatch') {
      $msg = "We didn't recognize your certificate, please ensure the provide path is a valid Lockr certificate.";
    }
    elseif ($e->title === 'Site exists') {
      $msg = 'This site is already registered. If you are experiencing issues, please contact support@lockr.io.';
    }
    elseif ($e->title === 'Credentials invalid') {
      $msg = 'The username and password did not match, please try again.';
    }
    else {
      $msg = 'An unknown error occurred, please try again later.';
    }

    drush_log($msg, 'error');
  }
  catch (LockrServerException $e) {
    drush_log('An unknown error occurred, please try again.', 'error');
  }
}

/**
 * Patches installed modules for Key.
 */
function drush_lockr_lockdown() {
  $raw_path = 'https://raw.githubusercontent.com/CellarDoorMedia/Lockr-Patches/master';

  $reg_file = "{$raw_path}/registry.json";
  drush_log("Downloading registry file: {$reg_file}.");
  $registry = file_get_contents($reg_file);
  $registry = json_decode($registry, TRUE);

  if (json_last_error() !== JSON_ERROR_NONE) {
    drush_log(
      'There was an error downloading the patch registry.',
      LogLevel::ERROR
    );
    return;
  }

  if (count($registry['drupal8']) < 1) {
    drush_log('There are no patches currently available for this version of Drupal. Check again later!');
    return;
  }

  $names = implode(', ', array_keys($registry['drupal8']));
  drush_log("Patches available for: {$names}.");
  foreach ($registry['drupal8'] as $name => $path) {
    // We use `drupal_get_path()' and check its result instead of
    // `module_exists()' because it will find disabled modules.
    try {
      $module_path = \Drupal::moduleHandler()->getModule($name)->getPath();
      $module_path = \Drupal::root() . $module_path;
    }
    catch (\InvalidArgumentException $e) {
      drush_log("Module not found: {$name}.");
      continue;
    }

    $module_path = DRUPAL_ROOT . '/' . $module_path;
    if (!is_dir($module_path)) {
      drush_log("Module path does not exist: {$module_path}");
      continue;
    }

    // The lockfile prevents double-patching a module if lockdown is
    // called more than once. Applying a patch more than once can be
    // disastrous, and we don't want that.
    $lockfile = "{$module_path}/.lockr-patched";
    if (is_file($lockfile)) {
      drush_log("{$name} already patched.");
      drush_log("Remove {$lockfile} to patch again.");
      drush_log("Do so at your own peril.");
      continue;
    }

    $patch_path = "{$module_path}/key-integration.patch";
    $patch_remote = "{$raw_path}/{$path}";
    drush_log("Downloading {$patch_remote}.");
    copy($patch_remote, $patch_path);

    drush_log("Patching {$name}.");
    $cmd = implode(' ', array(
      'patch',
      // We do not need a backup because reverting the patch can be done
      // via the user's version control system.
      '--no-backup-if-mismatch',
      '-N',
      '-p1',
      '-d', escapeshellarg($module_path),
      '<',  escapeshellarg($patch_path),
    ));
    drush_log("Running `{$cmd}`.");
    ob_start();
    passthru($cmd, $return_code);
    drush_log(ob_get_clean());

    if ($return_code === 0) {
      // Patch is OK, go ahead and write the lockfile and remove the
      // downloaded patch.
      drush_log("Patch successful, writing lockfile.");
      file_put_contents($lockfile, '');
      unlink($patch_path);
    }
    else {
      drush_log("Failed to patch {$name}.", 'error');
      drush_log("Patch file left at '{$patch_path}'.", 'error');
    }
  }
}
