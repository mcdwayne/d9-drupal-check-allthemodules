<?php

/**
 * @file
 * Handles navigation timing logging via AJAX with minimal bootstrap.
 */

/**
 * Define the root directory of the Drupal installation. Since we don't know
 * where the module is installed, we walk up the tree until we hit index.php.
 */

// We can't use getcwd / chdir('..') as PHP resolves any symlinks that might
// be in the path.
$cwd = dirname($_SERVER['SCRIPT_FILENAME']);
while ($cwd != '/' && chdir($cwd . '/..')) {
  $parts = explode('/', $cwd);
  $cwd = implode('/', array_slice($parts, 0, count($parts) - 1));
  $files = scandir($cwd);
  if (in_array('index.php', $files)) {
    define('DRUPAL_ROOT', getcwd());
    break;
  }
}

if (!defined('DRUPAL_ROOT')) {
  error_log("Navigation timing AJAX callback could not determine the Drupal root directory.");
  exit;
}

include_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);

/**
 * Saves navigation timing data to the database.
 *
 * @param $nt
 *   RAW $_POST['nt'] value.
 */
function navigation_timing_log($nt) {
  $data = !empty($nt) ? json_decode($nt, TRUE) : array();
  if (empty($data)) {
    print 'fail';
    drupal_exit();
  }
  // Default values for all available fields.
  $default = array(
    'gid' => NULL,
    'path' => '',
    'uid' => 0,
    'theme' => '',
    'js' => 1,
    'css' => 1,
    'ua' => '',
    'type' => NULL,
    'redirectCount' => NULL,
    'navigationStart' => NULL,
    'redirectStart' => NULL,
    'unloadEventStart' => NULL,
    'unloadEventEnd' => NULL,
    'redirectEnd' => NULL,
    'fetchStart' => NULL,
    'domainLookupStart' => NULL,
    'domainLookupEnd' => NULL,
    'connectStart' => NULL,
    'secureConnectionStart' => NULL,
    'connectEnd' => NULL,
    'requestStart' => NULL,
    'responseStart' => NULL,
    'responseEnd' => NULL,
    'domLoading' => NULL,
    'domInteractive' => NULL,
    'domContentLoadedEventStart' => NULL,
    'domContentLoadedEventEnd' => NULL,
    'domComplete' => NULL,
    'loadEventStart' => NULL,
    'loadEventEnd' => NULL,
    'firstPaint' => NULL,
    'clientWidth' => NULL,
    'clientHeight' => NULL,
    'extra' => '',
  );

  // If we're using the navigation trail module there is one more variable
  // to log.
  if (!empty($data['trailid'])) {
    $default['trailid'] = NULL;
  }

  // Special treatment for proprietary data.
  if (!empty($data['msFirstPaint'])) {
    $data['firstPaint'] = $data['msFirstPaint'];
    unset($data['msFirstPaint']);
  }

  // Filters data keys to allow only available columns.
  $data = array_intersect_key($data, array_flip(array_keys($default)));
  $nid = db_insert('navigation_timing_data')
    ->fields($data + $default)
    ->execute();
  return $nid;
}

// @todo add limits to avoid abuse.
$nid = navigation_timing_log($_POST['nt']);

// More data will be added later on.
drupal_add_http_header('Content-Type', 'application/json');
echo json_encode($nid, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

