<?php

/**
 * @file
 * Drush integration for ImageLightBox module.
 */

const IMAGELIGHTBOX_LIBRARY_DIR = 'libraries/imagelightbox';
const IMAGELIGHTBOX_MODULE_DIR = 'modules/imagelightbox';
const IMAGELIGHTBOX_DOWNLOAD_JS_URL = 'https://raw.githubusercontent.com/osvaldasvalutis/imageLightbox.js/master/imagelightbox.js';
const IMAGELIGHTBOX_DOWNLOAD_JS_MIN_URL = 'https://raw.githubusercontent.com/osvaldasvalutis/imageLightbox.js/master/imagelightbox.min.js';
const IMAGELIGHTBOX_DELIVERED_LIBRARIES = 'modules/imagelightbox/libraries';
const IMAGELIGHTBOX_INSTALL_ROOT_LIBRARIES_YAML = 'modules/imagelightbox/install/imagelightbox.libraries.yml.root';
const IMAGELIGHTBOX_INSTALL_MODULE_LIBRARIES_YAML = 'modules/imagelightbox/install/imagelightbox.libraries.yml.module';

/**
 * Implements hook_drush_command().
 */
function imagelightbox_drush_command() {
  $items['imagelightbox-download'] = [
    'description' => dt('Download and install the most recent version of ImageLightBox library.'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'aliases' => ['ilb-dl'],
    'arguments' => [
       'mod_update' => dt('Set the option (ex. "drush ilb-dl mod_update") to update the imagelightbox in the module/libraries-directory and overwrite the distributed files.
       
To download all files to the /libraries-directory, DO NOT SET this option (ex. "drush ilb-dl")!  '),
     ],
  ];
  return $items;
}

/**
 * Command to download the ImageLightBox library.
 */
function drush_imagelightbox_download($arg1 = '') {

  if ($arg1=='') {
    $libraries_dir = 'libraries';  
  }

    
  if ((!is_dir($libraries_dir))  && ($arg1=='')) {
    return drush_set_error('IMAGELIGHTBOX', dt('Directory @libraries_dir does not exist.', ['@libraries_dir' => $libraries_dir]));
  }

  if ((is_dir(IMAGELIGHTBOX_LIBRARY_DIR)) && ($arg1=='')) {
    if (drush_confirm(dt('Install location @dir already exists. Do you want to overwrite it?', ['@dir' => IMAGELIGHTBOX_LIBRARY_DIR]))) {
      drush_delete_dir(IMAGELIGHTBOX_LIBRARY_DIR, TRUE);
    }
  }

  if ($arg1=='mod_update') {
    drush_shell_exec('wget --timeout=15 -O %s %s', IMAGELIGHTBOX_DELIVERED_LIBRARIES . '/imagelightbox.js', IMAGELIGHTBOX_DOWNLOAD_JS_URL);
    drush_shell_exec('wget --timeout=15 -O %s %s', IMAGELIGHTBOX_DELIVERED_LIBRARIES . '/imagelightbox.min.js', IMAGELIGHTBOX_DOWNLOAD_JS_MIN_URL);
    $js_file_found = drush_file_not_empty(IMAGELIGHTBOX_DELIVERED_LIBRARIES . '/imagelightbox.js');
    $js_min_file_found = drush_file_not_empty(IMAGELIGHTBOX_DELIVERED_LIBRARIES . '/imagelightbox.min.js');
    drush_shell_exec('cp %s/* %s', IMAGELIGHTBOX_DELIVERED_LIBRARIES, IMAGELIGHTBOX_LIBRARY_DIR);
    drush_shell_exec('cp %s %s/imagelightbox.libraries.yml', IMAGELIGHTBOX_INSTALL_MODULE_LIBRARIES_YAML, IMAGELIGHTBOX_MODULE_DIR);
    $log_dir=IMAGELIGHTBOX_DELIVERED_LIBRARIES;
  } elseif ($arg1=='') {
    drush_mkdir(IMAGELIGHTBOX_LIBRARY_DIR);
    drush_shell_exec('wget --timeout=15 -O %s %s', IMAGELIGHTBOX_LIBRARY_DIR . '/imagelightbox.js', IMAGELIGHTBOX_DOWNLOAD_JS_URL);
    drush_shell_exec('wget --timeout=15 -O %s %s', IMAGELIGHTBOX_LIBRARY_DIR . '/imagelightbox.min.js', IMAGELIGHTBOX_DOWNLOAD_JS_MIN_URL);
    $js_file_found = drush_file_not_empty(IMAGELIGHTBOX_LIBRARY_DIR . '/imagelightbox.js');
    $js_min_file_found = drush_file_not_empty(IMAGELIGHTBOX_LIBRARY_DIR . '/imagelightbox.min.js');
    drush_shell_exec('cp %s/* %s', IMAGELIGHTBOX_DELIVERED_LIBRARIES, IMAGELIGHTBOX_LIBRARY_DIR);
    drush_shell_exec('cp %s %s/imagelightbox.libraries.yml', IMAGELIGHTBOX_INSTALL_ROOT_LIBRARIES_YAML, IMAGELIGHTBOX_MODULE_DIR);
    $log_dir=IMAGELIGHTBOX_LIBRARY_DIR;
  }
    
  if (!$js_file_found || !$js_min_file_found) {
    return drush_set_error('IMAGELIGHTBOX', dt('imagelightbox library has not been installed.'));
  }
  else {
    drush_log(dt('The newest ImageLightBox library has been installed in @path directory.', ['@path' => $log_dir]), 'success');
  }

  

}
