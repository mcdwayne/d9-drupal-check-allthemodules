<?php

/**
 * @file
 * Main index file for the phar library.
 */

if (!defined('DRD_ARCHIVE')) {
  define('DRD_ARCHIVE', 'drd-' . $_SERVER['HTTP_X_DRD_VERSION'] . '.phar');
}

if (!function_exists('drupal_realpath')) {

  /**
   * Fallback if this function doesn't exist.
   *
   * @param string $filename
   *   Filename lead by 'temporary://' which will be removed.
   *
   * @return string
   *   Full filename of DRD library in temp directory.
   */
  function drupal_realpath($filename) {
    return file_directory_temp() . str_replace('temporary://', '/', $filename);
  }

}

/**
 * Helper function to include other PHP file.
 *
 * @param string $filename
 *   Full qualified filename to load.
 */
function drd_agent_require_once($filename) {
  /* @noinspection PhpIncludeInspection */
  require_once $filename;
}

if (!defined('DRD_BASE')) {
  define('DRD_BASE', 'phar://' . drupal_realpath('temporary://' . DRD_ARCHIVE));
}
drd_agent_require_once(DRD_BASE . '/Action/BaseInterface.php');
drd_agent_require_once(DRD_BASE . '/Action/Base.php');
drd_agent_require_once(DRD_BASE . '/Auth/BaseInterface.php');
drd_agent_require_once(DRD_BASE . '/Auth/Base.php');
drd_agent_require_once(DRD_BASE . '/Remote/BaseInterface.php');
drd_agent_require_once(DRD_BASE . '/Remote/Base.php');
drd_agent_require_once(DRD_BASE . '/BaseInterface.php');
drd_agent_require_once(DRD_BASE . '/Base.php');
drd_agent_require_once(DRD_BASE . '/BaseMethodInterface.php');
drd_agent_require_once(DRD_BASE . '/BaseMethod.php');
