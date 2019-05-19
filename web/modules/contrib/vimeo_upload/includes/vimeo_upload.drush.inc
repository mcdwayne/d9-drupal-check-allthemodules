<?php

/**
 * @file
 * Provides drush commands for Vimeo Upload.
 *
 * Inspired from the rrssb module.
 */

/**
 * Implements hook_drush_command().
 */
function vimeo_upload_drush_command() {
  $items['vimeo-upload-plugin'] = [
    'callback' => 'drush_vimeo_upload_plugin',
    'description' => dt("Downloads Vimeo Upload library from Github."),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => [
      'path' => dt('Path is optional. Will use default location if omitted. Default location is /libraries.'),
    ],
    'aliases' => ['vudl'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function vimeo_upload_drush_help($section) {
  switch ($section) {
    case 'drush:vimeo-upload-plugin':
      return dt("Downloads the Vimeo Upload library from Github. Downloads to /libraries unless a location is provided.");
  }
}

/**
 * Implements drush_MODULE_post_pm_enable().
 */
function drush_vimeo_upload_post_pm_enable() {
  $modules = func_get_args();
  if (in_array('vimeo_upload', $modules)) {
    drush_vimeo_upload_plugin(NULL, TRUE);
  }
}

/**
 * Callback for the drush command to download the Vimeo Upload library.
 */
function drush_vimeo_upload_plugin($destination = NULL, $only_if_missing = FALSE) {
  if (!$destination) {
    $destination = 'libraries';
  }

  // The zip extracts to create a temporary directory which we then rename.
  // Delete existing files, including temporary ones if we failed part way
  // through.
  $library = "$destination/vimeo-upload";

  if (is_dir($library)) {
    if ($only_if_missing) {
      return;
    }
    drush_log(dt('Deleting existing Vimeo Upload plugin'), 'notice');
    drush_delete_dir($library, TRUE);
  }

  // Use the git API to find the latest library version.
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/websemantics/vimeo-upload/releases/latest');
  curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
  $info = json_decode(curl_exec($ch));
  curl_close($ch);

  $zip = drush_download_file($info->zipball_url);
  if (!$zip) {
    return drush_set_error(dt('Failed to download @url', ['@url' => $info->zipball_url]));
  }

  $listing = drush_tarball_extract($zip, $destination, TRUE);
  if (!$listing) {
    return drush_set_error(dt('Failed to extract to @dest', ['@dest' => $destination]));
  }
  unlink($zip);

  // Find the file extracted.
  // Workaround a drush bug where the first line is junk.
  do {
    $temp_library = $destination . '/' . array_shift($listing);
  } while (substr($temp_library, -1) != '/');

  // Patch the API
  // see issue https://github.com/r-daneelolivaw/vimeo_upload/issues/1
  // exec('patch -p1 < patches/issue-1-vimeo-api-version.patch');.
  // Move to the correct location.
  drush_move_dir($temp_library, $library, TRUE);

  if (!file_exists("$library/vimeo-upload.js")) {
    return(drush_set_error(dt('Drush was unable to download the Vimeo Upload library to @path', ['@path' => $library])));
  }

  drush_log(dt('The Vimeo Upload library has been downloaded to @path', ['@path' => $library]), 'success');
}
