<?php

/**
 * @file
 *   Provides Configuration Management commands.
 */

use Drupal\config\StorageReplaceDataWrapper;
use Drush\Log\LogLevel;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\FileStorage;
use Drupal\Component\Utility\NestedArray;
use Drush\Config\StorageWrapper;
use Drush\Config\CoreExtensionFilter;
use Symfony\Component\Yaml\Parser;

/**
 * Implementation of hook_drush_command().
 */
function drush_config_import_log_drush_command() {
  $deps = array('drupal dependencies' => array('config'));
  $items['custom-config-import'] = array(
    'description' => 'Import config from a config directory.',
    'arguments' => array(
      'label' => "A config directory label (i.e. a key in \$config_directories array in settings.php). Defaults to 'sync'",
    ),
    'options' => array(
      'preview' => array(
        'description' => 'Format for displaying proposed changes. Recognized values: list, diff. Defaults to list.',
        'example-value' => 'list',
      ),
      'source' => array(
        'description' => 'An arbitrary directory that holds the configuration files. An alternative to label argument',
      ),
      'partial' => array(
        'description' => 'Allows for partial config imports from the source directory. Only updates and new configs will be processed with this flag (missing configs will not be deleted).',
      ),
    ),
    'core' => array('8+'),
    'examples' => array(
      'drush config-import --partial' => 'Import configuration; do not remove missing configuration.',
    ),
    'aliases' => array('ccim'),
  );
  return $items;
}

/**
 * Command callback. Import from specified config directory (defaults to sync).
 */
function drush_drush_config_import_log_custom_config_import($source = NULL) {
  global $config_directories;

  // Determine source directory.
  if ($target = drush_get_option('source')) {
    $source_dir = $target;
  }
  else {
    $choices = drush_map_assoc(array_keys($config_directories));
    unset($choices[CONFIG_ACTIVE_DIRECTORY]);
    if (!isset($source) && count($choices) >= 2) {
      $source= drush_choice($choices, 'Choose a source.');
      if (empty($source)) {
        return drush_user_abort();
      }
    }
    elseif (!isset($source)) {
      $source = CONFIG_SYNC_DIRECTORY;
    }
    $source_dir = config_get_config_directory($source);
  }

  if ($source == CONFIG_SYNC_DIRECTORY) {
    $source_storage = \Drupal::service('config.storage.sync');
  }
  else {
    $source_storage = new FileStorage($source_dir);
  }
  
  $myfile = fopen("sites/default/files/custom_drush_config_import_log.txt", "w") or die("Unable to open file!");
  $log = "";
  fwrite($myfile,$log);
  // Determine $source_storage in partial and non-partial cases.
  /** @var \Drupal\Core\Config\StorageInterface $active_storage */
  $active_storage = \Drupal::service('config.storage');
  if (drush_get_option('partial')) {
    $replacement_storage = new StorageReplaceDataWrapper($active_storage);
    foreach ($source_storage->listAll() as $name) {
      $data = $source_storage->read($name);
      $replacement_storage->replaceData($name, $data);
    }
    $source_storage = $replacement_storage;
  }

  /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
  $config_manager = \Drupal::service('config.manager');
  $storage_comparer = new StorageComparer($source_storage, $active_storage, $config_manager);

  if (!$storage_comparer->createChangelist()->hasChanges()) {
    $msg_nthng_to_import = 'There are no changes to import.';
    \Drupal::logger('drush_config_import_log')->info($msg_nthng_to_import);
    fwrite($myfile,date("Y-m-d H:i:s") . " - Info: " . $msg_nthng_to_import . PHP_EOL);
    return drush_log(dt($msg_nthng_to_import), LogLevel::OK);
  }

  if (drush_get_option('preview', 'list') == 'list') {
    $change_list = array();
    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      $change_list[$collection] = $storage_comparer->getChangelist(NULL, $collection);
    }
    _drush_print_drush_config_import_log_changes_table($change_list);
  }
  else {
    // Copy active storage to the temporary directory.
    $temp_dir = drush_tempdir();
    $temp_storage = new FileStorage($temp_dir);
    $source_dir_storage = new FileStorage($source_dir);
    foreach ($source_dir_storage->listAll() as $name) {
      if ($data = $active_storage->read($name)) {
        $temp_storage->write($name, $data);
      }
    }
    drush_shell_exec('diff -x %s -u %s %s', '*.git', $temp_dir, $source_dir);
    $output = drush_shell_exec_output();
    drush_print(implode("\n", $output));
  }

  if (drush_confirm(dt('Import the listed configuration changes?'))) {
    return drush_op('_drush_drush_config_import_log_custom_config_import', $storage_comparer, $myfile);
  }
}

// Copied from submitForm() at /core/modules/config/src/Form/ConfigSync.php
function _drush_drush_config_import_log_custom_config_import(StorageComparer $storage_comparer, $myfile) {
  $config_importer = new ConfigImporter(
    $storage_comparer,
    \Drupal::service('event_dispatcher'),
    \Drupal::service('config.manager'),
    \Drupal::lock(),
    \Drupal::service('config.typed'),   
    \Drupal::moduleHandler(),
    \Drupal::service('module_installer'),
    \Drupal::service('theme_handler'),
    \Drupal::service('string_translation')
  );
  if ($config_importer->alreadyImporting()) {
      $msg_alrdy = 'Another request may be synchronizing configuration already.';
      drush_log($msg_alrdy, LogLevel::WARNING);
      \Drupal::logger('drush_config_import_log')->warning($msg_alrdy);
      fwrite($myfile,date("Y-m-d H:i:s") . " - warning: " . $msg_alrdy . PHP_EOL);
  }
  else{
    try {
      // This is the contents of \Drupal\Core\Config\ConfigImporter::import.
      // Copied here so we can log progress.
      if ($config_importer->hasUnprocessedConfigurationChanges()) {
        $sync_steps = $config_importer->initialize();
        foreach ($sync_steps as $step) {
          $context = array();
          do {
            $config_importer->doSyncStep($step, $context);
            if (isset($context['message'])) {
              $msg_current = str_replace('Synchronizing', 'Synchronized', (string)$context['message']);
              drush_log($msg_current, LogLevel::OK);
              \Drupal::logger('drush_config_import_log')->info($msg_current);
              fwrite($myfile,date("Y-m-d H:i:s") . " - Info: " . $msg_current . PHP_EOL);
            }
          } while ($context['finished'] < 1);
        }
      }
      if ($config_importer->getErrors()) {
        $msg_fail = 'Errors occurred during import';
        throw new \Drupal\Core\Config\ConfigException($msg_fail);
        \Drupal::logger('custom_drush_import_fail')->error($msg_fail);
        fwrite($myfile, date("Y-m-d H:i:s") . " - Error: " . $msg_fail . PHP_EOL);
      }
      else {
        $msg_success = 'The configuration was imported successfully.';
        drush_log($msg_success, LogLevel::SUCCESS);
        \Drupal::logger('custom_drush_import_success')->info($msg_success);
        fwrite($myfile, date("Y-m-d H:i:s") . " - Success: " . $msg_success . PHP_EOL);
      }
    }
    catch (ConfigException $e) {
      // Return a negative result for UI purposes. We do not differentiate
      // between an actual synchronization error and a failed lock, because
      // concurrent synchronizations are an edge-case happening only when
      // multiple developers or site builders attempt to do it without
      // coordinating.
      $message = 'The import failed due for the following reasons:' . "\n";
      $message .= implode("\n", $config_importer->getErrors());
      \Drupal::logger('custom_drush_import_fail')->error($message);
      fwrite($myfile,date("Y-m-d H:i:s") . " - Error: " . $message . PHP_EOL);
      watchdog_exception('config_import', $e);
      fclose($myfile);
      return drush_set_error('config_import_fail', $message);
    }
  }
  fclose($myfile);
}

/**
 * Print a table of config changes.
 *
 * @param array $config_changes
 *   An array of changes keyed by collection.
 */
function _drush_format_drush_config_import_log_changes_table(array $config_changes, $use_color = FALSE) {
  if (!$use_color) {
    $red = "%s";
    $yellow = "%s";
    $green = "%s";
  }
  else {
    $red = "\033[31;40m\033[1m%s\033[0m";
    $yellow = "\033[1;33;40m\033[1m%s\033[0m";
    $green = "\033[1;32;40m\033[1m%s\033[0m";
  }

  $rows = array();
  $rows[] = array('Collection', 'Config', 'Operation');
  foreach ($config_changes as $collection => $changes) {
    foreach ($changes as $change => $configs) {
      switch ($change) {
        case 'delete':
          $colour = $red;
          break;
        case 'update':
          $colour = $yellow;
          break;
        case 'create':
          $colour = $green;
          break;
        default:
          $colour = "%s";
          break;
      }
      foreach($configs as $config) {
        $rows[] = array(
          $collection,
          $config,
          sprintf($colour, $change)
        );
      }
    }
  }
  $tbl = _drush_format_table($rows);
  return $tbl;
}

/**
 * Print a table of config changes.
 *
 * @param array $config_changes
 *   An array of changes keyed by collection.
 */
function _drush_print_drush_config_import_log_changes_table(array $config_changes) {
  $tbl =  _drush_format_drush_config_import_log_changes_table($config_changes, !drush_get_context('DRUSH_NOCOLOR'));

  $output = $tbl->getTable();
  if (!stristr(PHP_OS, 'WIN')) {
    $output = str_replace("\r\n", PHP_EOL, $output);
  }

  drush_print(rtrim($output));
  return $tbl;
}

/**
 * Command argument complete callback.
 */
function config_drush_config_import_log_import_complete() {
  return _drush_drush_config_import_log_directories_complete();
}

/**
 * Helper function for command argument complete callback.
 *
 * @return
 *   Array of available config directories.
 */
function _drush_drush_config_import_log_directories_complete() {
  drush_bootstrap_max(DRUSH_BOOTSTRAP_DRUPAL_CONFIGURATION);
  global $config_directories;
  return array('values' => array_keys($config_directories));
}
