<?php

namespace Drupal\potx\Commands;

use Drush\Commands\DrushCommands;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class PotxCommands.
 *
 * Translation template extractor module drush integration.
 *
 * @package Drupal\potx\Commands
 */
class PotxCommands extends DrushCommands {

  /**
   * Extract translatable strings from Drupal source code.
   *
   * @param string $mode
   *   The output mode e.g. single multiple core.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *    config, etc.
   *
   * @command potx
   *
   * @option modules Comma delimited list of modules to extract translatable strings from.
   * @option files Comma delimited list of files to extract translatable strings from.
   * @option folder Folder to begin translation extraction in. When no other option is set this defaults to current directory.
   * @option api Drupal core version to use for extraction settings.
   * @usage potx single Extract translatable strings from applicable files in current directory and write to single output file.
   * @usage potx multiple --modules=example Extract translatable strings from applicable files of example module and write to module-specific output file.
   * @usage potx --files=sites/all/modules/example/example.module Extract translatable strings from example.module and write to single output file.
   * @usage potx single --api=8 --folder=projects/drupal/8 Extract strings from folder projects/drupal/8 using API version 8.
   * @aliases
   * @field-labels
   *   files: Files
   *   strings: Strings
   *   warnings: Warnings
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The number of files, strings, and errors processed, in a table format.
   */
  public function potx($mode = NULL, array $options = [
    'modules' => NULL,
    'files' => NULL,
    'folder' => NULL,
    'api' => NULL,
  ]) {
    // Include library.
    include_once __DIR__ . '/../../potx.inc';
    include_once __DIR__ . '/../../potx.local.inc';

    $files = [];
    $build_mode = POTX_BUILD_SINGLE;

    if (!is_null($mode) && in_array($mode, ['core', 'multiple', 'single'])) {
      // First argument could be any of the mode names.
      $build_mode = constant('POTX_BUILD_' . strtoupper($mode));
    }
    // Silence error message reporting. Messages will be reported by at the end.
    potx_status('set', POTX_STATUS_SILENT);

    // Get Drush options.
    $modules_option = $options['modules'];
    $files_option = $options['files'];
    $folder_option = $options['folder'];
    $api_option = $options['api'];
    if (empty($api_option) || !in_array($api_option, [5, 6, 7, 8])) {
      $api_option = POTX_API_CURRENT;
    }

    potx_local_init($folder_option);

    if (!empty($modules_option)) {
      $modules = explode(',', $modules_option);
      foreach ($modules as $module) {
        $files = array_merge($files, _potx_explore_dir(drupal_get_path('module', $module) . '/', '*', $api_option, TRUE));
      }
    }
    elseif (!empty($files_option)) {
      $files = explode(',', $files_option);
    }
    elseif (!empty($folder_option)) {
      $files = _potx_explore_dir($folder_option, '*', $api_option, TRUE);
    }
    else {
      // No file list provided so autodiscover files in current directory.
      $files = _potx_explore_dir(getcwd() . '/', '*', $api_option, TRUE);
    }

    foreach ($files as $file) {
      $this->output()->writeln(dt("Processing $file..."));
      _potx_process_file($file, 0, '_potx_save_string', '_potx_save_version', $api_option);
    }

    potx_finish_processing('_potx_save_string', $api_option);

    _potx_build_files(POTX_STRING_RUNTIME, $build_mode);
    _potx_build_files(POTX_STRING_INSTALLER, POTX_BUILD_SINGLE, 'installer');
    _potx_write_files();

    $this->output()->writeln("");

    // Get errors, if any.
    $errors = potx_status('get');
    // Get saved strings.
    $strings = _potx_save_string(NULL, NULL, NULL, 0, POTX_STRING_RUNTIME);
    $rows[] = [
      'files' => count($files),
      'strings' => count($strings),
      'errors' => count($errors),
    ];

    if (!empty($errors)) {
      $this->output()->writeln(dt("Errors"));
      foreach ($errors as $error) {
        $this->logger()->error($error);
      }
    }

    return new RowsOfFields($rows);
  }

}
