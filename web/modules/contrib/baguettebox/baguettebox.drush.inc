<?php

/**
 * @file
 * Drush 8 integration for BaguetteBox module.
 */

const BAGUETTEBOX_LIBRARY_DIR = 'libraries/baguettebox';
const BAGUETTEBOX_DOWNLOAD_JS_URL = 'https://raw.githubusercontent.com/feimosi/baguetteBox.js/master/dist/baguetteBox.min.js';
const BAGUETTEBOX_DOWNLOAD_CSS_URL = 'https://raw.githubusercontent.com/feimosi/baguetteBox.js/master/dist/baguetteBox.min.css';

/**
 * Implements hook_drush_command().
 */
function baguettebox_drush_command() {
  $items['baguettebox-download'] = [
    'description' => dt('Download and install the most recent version of BaguetteBox library.'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'aliases' => ['bb-d'],
  ];
  return $items;
}

/**
 * Command to download the BaguetteBox library.
 */
function drush_baguettebox_download() {

  $libraries_dir = 'libraries';

  if (!is_dir($libraries_dir)) {
    return drush_set_error('BAGUETTEBOX', dt('Directory @libraries_dir does not exist.', ['@libraries_dir' => $libraries_dir]));
  }

  if (is_dir(BAGUETTEBOX_LIBRARY_DIR)) {
    if (drush_confirm(dt('Install location @dir already exists. Do you want to overwrite it?', ['@dir' => BAGUETTEBOX_LIBRARY_DIR]))) {
      drush_delete_dir(BAGUETTEBOX_LIBRARY_DIR, TRUE);
    }
    else {
      return 0;
    }
  }

  drush_mkdir(BAGUETTEBOX_LIBRARY_DIR);

  drush_shell_exec('wget --timeout=15 -O %s %s', BAGUETTEBOX_LIBRARY_DIR . '/baguetteBox.min.js', BAGUETTEBOX_DOWNLOAD_JS_URL);
  drush_shell_exec('wget --timeout=15 -O %s %s', BAGUETTEBOX_LIBRARY_DIR . '/baguetteBox.min.css', BAGUETTEBOX_DOWNLOAD_CSS_URL);

  $js_file_found = drush_file_not_empty(BAGUETTEBOX_LIBRARY_DIR . '/baguetteBox.min.js');
  $css_file_found = drush_file_not_empty(BAGUETTEBOX_LIBRARY_DIR . '/baguetteBox.min.css');
  if (!$js_file_found || !$css_file_found) {
    return drush_set_error('BAGUETTEBOX', dt('Could not download baguetteBox library from GitHub.'));
  }
  else {
    drush_log(dt('BaguetteBox library has been installed in @path directory.', ['@path' => BAGUETTEBOX_LIBRARY_DIR]), 'success');
  }

}
