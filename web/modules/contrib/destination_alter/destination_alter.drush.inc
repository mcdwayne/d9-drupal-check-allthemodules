<?php
/**
 * @file
 * Destination alteration for Drush downloading.
 */

/**
 * Implements hook_drush_pm_download_destination_alter().
 */
function destination_alter_drush_pm_download_destination_alter(array &$project) {
  if (function_exists('drupal_get_path')) {
    switch ($project['project_type']) {
      case 'module':
      case 'theme':
        $project['project_install_location'] = sprintf(
          '%s/%ss/contrib', _destination_alter_download_path(), $project['project_type']
        );
        break;
    }
  }
}

/**
 * Determine downloading destination.
 *
 * If you want omit the -l option for every single call then
 * set $options['l'] = '<VALUE>'; in your drushrc.php.
 *
 * @internal
 *
 * @return string
 *   Relative path for downloading.
 */
function _destination_alter_download_path() {
  // @example
  // drush dl file_md5 -l profile -y
  $uri = drush_get_option('uri');

  if ('profile' === $uri) {
    return drupal_get_path($uri, drupal_get_profile());
  }

  $path = drush_conf_path($uri);

  // If "--uri" or "-l" parameter specified, then check that configuration
  // path exists or user specially chosen "default" directory.
  //
  // @example
  // drush dl managed_file -l [all|default|<SITE>] -y
  if (!isset($path) || ('sites/default' === $path && 'default' !== $uri)) {
    // Sitewide directory can be an empty string in case if we dealing
    // with Drupal 8. We cannot leave an empty string as a result because
    // downloading will be performed to root directory of file system.
    // The "." assumes that directory will be the root directory of Drupal
    // installation, so package will be located in "./modules/".
    return drush_drupal_sitewide_directory() ?: '.';
  }

  // @example
  // drush dl menu_image -l example.com -y
  return $path;
}
