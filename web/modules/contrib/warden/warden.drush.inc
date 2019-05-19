<?php

/**
 * @file
 * Drush integration with the Warden module.
 */

use Drupal\warden\Service\WardenManager;

 /**
  * Implements of hook_drush_command().
  */
function warden_drush_command() {
  $items = array();

  $items['warden-check'] = array(
    'description' => 'Test the connection to Warden by getting its public key',
  );

  $items['warden-update'] = array(
    'description' => 'Update Warden with the lastest site data',
  );

  $items['warden-show-module-data'] = array(
    'description' => 'Displays the module data that will be sent to Warden',
  );

  return $items;
}

/**
 * Test the connection to warden.
 */
function drush_warden_check() {
  try {
    $warden = _warden_get_manager();
    drush_print(dt('URL: :url', [':url' => $warden->getWardenUrl()]));

    if ($warden->hasBasicAuthentication()) {
      drush_print(dt('HTTP Username: :username', [':username' => $warden->getUsername()]));
      drush_print(dt('HTTP Password: :password', [':password' => $warden->getPassword()]));
    }

    if ($warden->hasCertificatePath()) {
      drush_print(dt('Certificate file: :path', [':path' => $warden->getCertificatePath()]));
    }

    $key = $warden->getPublicKey();

    drush_print(dt('Going to check connection to Warden server by retrieving the public key ...'));
    drush_print($key);
  }
  catch (Exception $e) {
    drush_set_error($e->getMessage());
  }
}

/**
 * Test the connection to warden.
 */
function drush_warden_update() {
  drush_print(dt('Going to update Warden ...'));

  try {
    _warden_get_manager()->updateWarden();
    drush_print(dt('... success'));
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }
}

/**
 * Prints out the details of the data that the will be reported to Warden.
 *
 * This shows the module name and version data that will be reported.
 */
function drush_warden_show_module_data() {
  module_load_include('inc', 'warden', 'warden.page');
  $data = _warden_get_manager()->generateSiteData();
  drush_print_r($data);
}

/**
 * @return WardenManager
 */
function _warden_get_manager() {
  return \Drupal::service('warden.manager');
}