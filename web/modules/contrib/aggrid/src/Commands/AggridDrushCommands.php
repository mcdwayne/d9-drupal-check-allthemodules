<?php

namespace Drupal\aggrid\Commands;

use Drush\Commands\DrushCommands;

/**
 * Class AggridDrushCommands.
 *
 * @package Drupal\aggrid\Commands
 */
class AggridDrushCommands extends DrushCommands {

  /**
   * Downloads the most current ag-Grid Library files.
   *
   *   Argument provided to the drush command.
   *
   * @command aggrid:download
   * @aliases agg-download
   * @usage aggrid:download
   *   Download/Update the ag-Grid Library from GitHub
   */
  public function download() {
    // Create a file system service manager.
    // Remove the existing directory if it exists.
    $library_directory = DRUPAL_ROOT . '/libraries/';
    $clear_cache = false;

    $aggrid_library_directory = $library_directory . '/ag-grid';
    if (file_exists($library_directory) && file_exists($aggrid_library_directory)) {
      // Remove the existing file.
      if (file_exists($aggrid_library_directory . '/ag-grid-community.min.noStyle.js')) {
        unlink($aggrid_library_directory . '/ag-grid-community.min.noStyle.js');
      }
      if (file_exists($aggrid_library_directory . '/ag-grid-enterprise.min.noStyle.js')) {
        unlink($aggrid_library_directory . '/ag-grid-enterprise.min.noStyle.js');
      }
      rmdir($aggrid_library_directory);
    }

    // Create the directory(s).
    if (!file_exists($library_directory)) {
      mkdir(DRUPAL_ROOT . '/libraries');
    }
    mkdir(DRUPAL_ROOT . '/libraries/ag-grid');

    // Download the community file.
    if (drush_shell_exec('wget https://github.com/ag-grid/ag-grid/raw/master/packages/ag-grid-community/dist/ag-grid-community.min.noStyle.js -O ' . DRUPAL_ROOT . '/libraries/ag-grid' . '/ag-grid-community.min.noStyle.js')) {
      drush_log(dt('ag-Grid Community library has been successfully installed at libraries/ag-grid', [], 'success'), 'success');
      $clear_cache = true;
    }
    else {
      drush_log(dt('Error: unable to install ag-Grid Community library', [], 'error'), 'error');
    }

    // Download the enterprise file.
    if (drush_shell_exec('wget https://github.com/ag-grid/ag-grid/raw/master/packages/ag-grid-enterprise/dist/ag-grid-enterprise.min.noStyle.js  -O ' . DRUPAL_ROOT . '/libraries/ag-grid' . '/ag-grid-enterprise.min.noStyle.js')) {
      drush_log(dt('ag-Grid Enterprise library has been successfully installed at libraries/ag-grid', [], 'success'), 'success');
      $clear_cache = true;
    }
    else {
      drush_log(dt('Error: unable to install ag-Grid Enterprise library', [], 'error'), 'error');
    }

    // Clear the cache
    if ($clear_cache) {
      drupal_flush_all_caches();
    }

  }

}
