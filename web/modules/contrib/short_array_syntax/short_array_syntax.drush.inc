<?php
/**
 * @file
 * Converts PHP's array() syntax to PHP 5.4's short array syntax[].
 */

/**
 * Implements hook_drush_help().
 */
function short_array_syntax_drush_help($section) {
  switch ($section) {
    case 'drush:short-array-syntax':
      return dt("Converts PHP's array() syntax to PHP 5.4's short array syntax[].");
  }
}

/**
 * Implements hook_drush_command().
 */
function short_array_syntax_drush_command() {
  $items = array();
  $items['short-array-syntax'] = [
    'description' => "Converts PHP's array() syntax to PHP 5.4's short array syntax[].",
    'arguments' => [
      'module' => 'Module name.',
    ],
    'examples' => [
      'drush short-array-syntax example' => "Converts example.module's PHP array() syntax to PHP 5.4's short array syntax[].",
    ],
  ];
  return $items;
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_short_array_syntax_validate($module = NULL) {
  $path = drupal_get_path('module', $module);
  if (!$path) {
    return drush_set_error(dt("Unable to get '@module' path.", ['@module' => $module]));
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_short_array_syntax($module = NULL) {
  $convert_command = __DIR__ . '/convert.php';

  // Download convert.php.
  if (!file_exists($convert_command)) {
    drush_print(dt("Downloading 'convert.php' command..."));
    // ISSUE: the drush_download_file() is not working as expected so I am using
    // the private _drush_download_file() function.
    _drush_download_file('https://raw.githubusercontent.com/thomasbachem/php-short-array-syntax-converter/master/convert.php', $convert_command);
  }

  // Convert module's PHP's array() syntax to PHP 5.4\'s short array syntax[].
  $module_directory = DRUPAL_ROOT . '/' . drupal_get_path('module', $module);
  $extensions = ['inc', 'install', 'module', 'php'];
  foreach ($extensions as $extension) {
    drush_print(dt('Convert *.@extension file to short array syntax...', ['@extension' => $extension]));
    drush_shell_exec('find \'' . $module_directory .'\' -name "*.' . $extension . '" -exec php "' . $convert_command . '" -w "{}" \;');
    if ($output = drush_shell_exec_output()) {
      drush_print(implode("\n", drush_shell_exec_output()));
    }
    else {
      drush_print(dt('No *.@extension files.', ['@extension' => $extension]));
    }
  }
}

