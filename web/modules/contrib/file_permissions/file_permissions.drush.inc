<?php
/**
 * @file
 * Provide Drush integration for File permissions module.
 */

define('DS', DIRECTORY_SEPARATOR);

/**
 * Implements hook_drush_help().
 */
function file_permissions_drush_help($section) {
  switch ($section) {
    case 'drush:file-permissions':
      return dt('Rebuild your local environment files permissions.');
  }
}

/**
 * Implements hook_drush_command().
 */
function file_permissions_drush_command() {
  $items = array();

  $items['file-permissions'] = array(
    'description' => 'Rebuild your local environment files permissions.',
    'callback' => 'drush_file_permissions',
    // Need FULL bootstrap level to be able to use variable_get().
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => array('fp'),
  );

  return $items;
}

/**
 * Set file permissions.
 */
function drush_file_permissions() {
  ini_set('memory_limit', -1);
  // Before calling this drush needs to be bootstrapped to
  // DRUPAL_BOOTSTRAP_DATABASE.
  if (!drush_bootstrap_to_phase(DRUSH_BOOTSTRAP_DRUPAL_DATABASE)) {
    return drush_set_error('DRUPAL_SITE_NOT_FOUND', dt('You need to specify an alias or run this command within a Drupal site.'));
  }

  // Define all files to be included.
  $includes = array(
    DRUPAL_ROOT . DS . 'includes' . DS . 'bootstrap.inc',
    DRUPAL_ROOT . DS . 'includes' . DS . 'module.inc',
    DRUPAL_ROOT . DS . 'includes' . DS . 'file.inc',
  );

  // Load all needed files.
  foreach ($includes as $include) {
    if (file_exists($include)) {
      require_once $include;
    }
  }

  // Define default paths and variables.
  $default = 'sites' . DS . 'default';
  $private = $default . DS . 'private';
  $files = $default . DS . 'files';
  $directories = array();

  // Check for Private Files subdirectory.
  $private_directory = _drush_file_private_path();
  if (empty($private_directory)) {
    $private_directory = $private;
  }
  $directories[] = $private_directory;

  // Check for Public Files subdirectory.
  $public_directory = _drush_file_public_path();
  if (empty($public_directory)) {
    $public_directory = $files;
  }
  $directories[] = $public_directory;

  // In Drupal 8 process the config subdirectory as well.
  if (!empty($GLOBALS['config_directories'])) {
    foreach ($GLOBALS['config_directories'] as $type => $directory) {
      $directories[] = config_get_config_directory($type);
    }
  }

  // Sometimes sites/default subdirectory is not set to 755, ensure it is.
  chmod($default, 0755);

  // Get apache user from running processes.
  // Currently supported: apache2 and nginx.
  $user = drush_shell_exec('ps axho user,group,comm|grep -E "httpd|apache|nginx"|uniq|grep -v "root"|awk \'END {if ($1 && $2) print $1}\'');
  $user = current(drush_shell_exec_output());
  drush_log(dt('Your httpd user: ' . $user), 'status');

  // Get first group of apache user.
  $groups = drush_shell_exec('id -Gn ' . $user);
  $groups = current(drush_shell_exec_output());
  $groups = explode(' ', $groups);
  $group = current($groups);
  drush_log(dt('Your httpd group: ' . $group), 'status');

  drush_log(dt('You will be prompted for admin password to be able to change permissions with `sudo` command.'), 'warning');

  // Change settings.php permissions to 444 as per:
  // https://www.drupal.org/documentation/install/settings-file
  drush_shell_exec('nohup chmod 444 ' . $default . DS . 'settings.php  > /dev/null');

  // Process all gathered subdirectories.
  if (!empty($directories)) {
    foreach ($directories as $directory) {
      _file_permissions_mkdir($directory);
      _file_permissions_set($directory, $user, $group);
    }
  }

  // Create .htaccess files in public, private and temp subdirectories.
  file_ensure_htaccess();
}

/**
 * Set subdirectory permissions.
 */
function _file_permissions_set($directory, $user, $group) {
  // If directory is not relative use default Drupal docroot.
  if (!drush_is_absolute_path($directory)) {
    $directory = DRUPAL_ROOT . DS . $directory;
  }

  // Set file permissions.
  drush_shell_exec('nohup sudo chown -Rv ' . $user . ':' . $group . ' ' . $directory . '  > /dev/null');
  drush_shell_exec('nohup sudo chgrp -Rv ' . $group . ' ' . $directory . '  > /dev/null');
  // The 2 means that the group id will be preserved for any new files created
  // in this directory. What that means is that `www-data` will always be the
  // group on any files, thereby ensuring that web server and the user will both
  // always have write permissions to any new files that are placed in this
  // directory.
  drush_shell_exec('nohup sudo chmod -R 2775 ' . $directory . '  > /dev/null');
}

/**
 * Create subdirectory if it doesn't exist.
 */
function _file_permissions_mkdir($directory) {
  // If directory is relative (not starting with /), lets create it in
  // Drupal docroot.
  if (!drush_is_absolute_path($directory)) {
    $directory = DRUPAL_ROOT . DS . $directory;
  }

  if (!file_exists($directory)) {
    $mkdir = drush_mkdir($directory, TRUE);
    if (!$mkdir) {
      drush_log(dt('Attempted to create non existent @dir directory. Permission denied.', array(
        '@dir' => $directory,
      )), 'warning');
      exit;
    }
    else {
      drush_log(dt('Created non existent @dir directory.', array(
        '@dir' => $directory,
      )), 'status');
    }
  }
}
