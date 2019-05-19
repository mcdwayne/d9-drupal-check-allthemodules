<?php

/**
 * @file
 * Drush integration for vegas.
 */

/**
 * The vegas plugin URI.
 */
define('VEGAS_DOWNLOAD_URI', 'https://github.com/jaysalvat/vegas/archive/v2.4.0.zip');
define('VEGAS_DOWNLOAD_PREFIX', 'vegas-');

/**
 * Implements hook_drush_command().
 */
function vegas_drush_command() {
    $items = array();

    // The key in the $items array is the name of the command.
    $items['vegas-plugin'] = array(
        'callback' => 'drush_vegas_plugin',
        'description' => dt('Download/install the vegas plugin.'),
        // No bootstrap.
        'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
        'arguments' => array(
            'path' => dt('Optional. A path where to install the Vegas plugin. If omitted Drush will use the default location.'),
        ),
        'aliases' => array('vegas'),
    );

    return $items;
}

/**
 * Implements hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 */
function vegas_drush_help($section) {
    switch ($section) {
        case 'drush:vegas-plugin':
            return dt('Download/install the vegas plugin from jaysalvat/vegas, default location is the libraries directory.');
    }
}

/**
 * Command to download the vegas plugin.
 */
function drush_vegas_plugin() {

    $args = func_get_args();
    if (!empty($args[0])) {
        $path = $args[0];
    }
    else {
        $path = 'libraries';
    }

    // Create the path if it does not exist.
    if (!is_dir($path)) {
        drush_op('mkdir', $path);
        drush_log(dt('Directory @path was created', array('@path' => $path)), 'notice');
    }

    // Set the directory to the download location.
    $olddir = getcwd();
    chdir($path);

    // Download the zip archive.
    if ($filepath = drush_download_file(VEGAS_DOWNLOAD_URI)) {

        $filename = basename($filepath);
        $dirname = VEGAS_DOWNLOAD_PREFIX . basename($filepath, '.zip');

        // Remove any existing vegas plugin directory.
        if (is_dir($dirname) || is_dir('vegas')) {
            drush_delete_dir($dirname, TRUE);
            drush_delete_dir('vegas', TRUE);
            drush_log(dt('A existing vegas plugin was deleted from @path', array('@path' => $path)), 'notice');
        }

        // Decompress the zip archive.
        drush_tarball_extract($filename);

        // Change the directory name to "vegas" if needed.
        if ($dirname != 'vegas') {
            drush_move_dir('vegas-2.4.0/dist', 'vegas', TRUE);
            $dirname = 'vegas';
            drush_delete_dir('vegas-2.4.0', TRUE);
        }
    }

    if (is_dir($dirname)) {
        drush_log(dt('vegas plugin has been installed in @path', array('@path' => $path)), 'success');
    }
    else {
        drush_log(dt('Drush was unable to install the vegas plugin to @path', array('@path' => $path)), 'error');
    }

    // Set working directory back to the previous working directory.
    chdir($olddir);
}
