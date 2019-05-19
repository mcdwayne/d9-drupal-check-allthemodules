<?php

/**
 * @file
 * Drush integration for getID3.
 */

/**
 * Implements hook_drush_command().
 */
function views_rss_media_drush_command() {
  $items['getid3-download'] = array(
    'callback' => 'views_rss_media_getid3_download',
    'description' => dt('Downloads the required getID3 library from SourceForge.net.'),
    'arguments' => array(
      'path' => dt('Optional. A path to the download folder. If omitted Drush will use the default location (sites/all/libraries/getid3).'),
    ),
  );
  return $items;
}

/**
 * Implements drush_MODULE_pre_COMMAND().
 *
 * This automatically downloads the library when the module is being installed.
 */
function drush_views_rss_media_pre_pm_enable() {
  $modules = func_get_args();
  if (in_array('views_rss_media', $modules)) {
    views_rss_media_getid3_download();
  }
}

/**
 * Download the getID3 library from SourceForge.
 */
function views_rss_media_getid3_download($library_path = 'libraries') {
  $library_path .= '/getID3';

  $version = '1.9.8';
  $filename = 'getID3-1.9.8-20140511.zip';
  $md5 = "64712d3562e60fae6be887c4aab85a27";
  $url = "http://downloads.sourceforge.net/project/getid3/getID3%28%29%201.x/{$version}/{$filename}";

  // Create the directory.
  if (!drush_shell_exec('mkdir -p  %s', $library_path)) {
    return drush_set_error('VIEWS_RSS_MEDIA_MKDIR', dt('Drush was unable to create the getID3 directory at @path.', array('@path' => $library_path)));
  }
  $zipfile_path = $library_path . '/' . $filename;
  drush_register_file_for_deletion($zipfile_path);
  // Download the file.
  if (!drush_shell_exec('wget -O %s %s', $zipfile_path, $url)) {
    return drush_set_error('VIEWS_RSS_MEDIA_FETCH', dt('Drush was unable to download the getID3 library to @path.', array('@path' => $zipfile_path)));
  }
  // Check MD5 hash.
  if (md5_file($zipfile_path) != $md5) {
    return drush_set_error('VIEWS_RSS_MEDIA_MD5', dt('The downloaded file @path was corrupted. Expected MD5 checksum: @md5.', array('@path' => $zipfile_path, '@md5' => $md5)));
  }
  // Unzip it the file -- using the "update" option to avoid being prompted
  // about overwriting files.
  if (!drush_shell_exec('unzip -u %s -d %s', $zipfile_path, $library_path)) {
    return drush_set_error('VIEWS_RSS_MEDIA_UNZIP', dt('Drush was unable to unzip the archive @path.', array('@path' => $zipfile_path)));
  }
  // Delete the demos. They're a security risk.
  $demos_path = $library_path . '/demos';
  if (!drush_shell_exec('rm -r %s', $demos_path)) {
    return drush_set_error('VIEWS_RSS_MEDIA_RM', dt("Drush was unable to remove getID3's demos directory. You should do so manually.", array('@path' => $demos_path)));
  }

  drush_log(dt('getID3 has been downloaded to @path.', array('@path' => $library_path)), 'success');
}
