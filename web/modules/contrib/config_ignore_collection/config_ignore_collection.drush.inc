<?php

/**
 * @file
 * Override config import/export drush commands.
 */

use Drupal\config\StorageReplaceDataWrapper;
use Drush\Log\LogLevel;
use Drupal\Core\Config\FileStorage;
use Drupal\config_ignore_collection\StorageComparer;

/**
 * Implements hook_drush_command_alter().
 */
function config_ignore_collection_drush_command_alter(&$command) {
  if ($command['command'] == 'config-import') {
    $command['callback'] = 'config_ignore_collection_drush_config_import';
  }
  elseif ($command['command'] == 'config-export') {
    $command['callback'] = 'config_ignore_collection_drush_config_export';
  }
}

/**
 * Command callback. Import from specified config directory (defaults to sync).
 */
function config_ignore_collection_drush_config_import($source = NULL) {
  global $config_directories;

  // Determine source directory.
  if ($target = drush_get_option('source')) {
    $source_dir = $target;
  }
  else {
    $choices = drush_map_assoc(array_keys($config_directories));
    unset($choices[CONFIG_ACTIVE_DIRECTORY]);
    if (!isset($source) && count($choices) >= 2) {
      $source = drush_choice($choices, 'Choose a source.');
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
    return drush_log(dt('There are no changes to import.'), LogLevel::OK);
  }

  if (drush_get_option('preview', 'list') == 'list') {
    $change_list = [];
    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      $change_list[$collection] = $storage_comparer->getChangelist(NULL, $collection);
    }
    _drush_print_config_changes_table($change_list);
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
    return drush_op('_drush_config_import', $storage_comparer);
  }
}

/**
 * Command callback: Export config to specified directory (usually sync).
 */
function config_ignore_collection_drush_config_export($destination = NULL) {
  global $config_directories;

  // Determine which target directory to use.
  if ($target = drush_get_option('destination')) {
    if ($target === TRUE) {
      // User did not pass a specific value for --destination. Make one.
      /** @var drush_version_control_backup $backup */
      $backup = drush_include_engine('version_control', 'backup');
      $destination_dir = $backup->prepare_backup_dir('config-export');
    }
    else {
      $destination_dir = $target;
      // It is important to be able to specify a destination directory that
      // does not exist yet, for exporting on remote systems.
      drush_mkdir($destination_dir);
    }
  }
  else {
    $choices = drush_map_assoc(array_keys($config_directories));
    unset($choices[CONFIG_ACTIVE_DIRECTORY]);
    if (!isset($destination) && count($choices) >= 2) {
      $destination = drush_choice($choices, 'Choose a destination.');
      if (empty($destination)) {
        return drush_user_abort();
      }
    }
    elseif (!isset($destination)) {
      $destination = CONFIG_SYNC_DIRECTORY;
    }
    $destination_dir = config_get_config_directory($destination);
  }

  // Prepare a new branch, if applicable.
  $remote = drush_get_option('push', FALSE);
  $original_branch = FALSE;
  $branch = FALSE;
  if ($remote) {
    // Get the branch that we're on at the moment.
    $result = drush_shell_cd_and_exec($destination_dir, 'git rev-parse --abbrev-ref HEAD');
    if (!$result) {
      return drush_set_error('DRUSH_CONFIG_EXPORT_NO_GIT', dt("The drush config-export command requires that the selected configuration directory !dir be under git revision control when using --commit or --push options.", ['!dir' => $destination_dir]));
    }
    $output = drush_shell_exec_output();
    $original_branch = $output[0];
    $branch = drush_get_option('branch', FALSE);
    if (!$branch) {
      $branch = $original_branch;
    }
    if ($branch != $original_branch) {
      // Switch to the working branch; create it if it does not exist.
      // We do NOT want to use -B here, as we do NOT want to reset the
      // branch if it already exists.
      $result = drush_shell_cd_and_exec($destination_dir, 'git checkout %s', $branch);
      if (!$result) {
        $result = drush_shell_cd_and_exec($destination_dir, 'git checkout -b %s', $branch);
      }
    }
  }

  // Do the actual config export operation.
  $result = _config_ignore_collection_drush_config_export($destination, $destination_dir, $branch);

  // Regardless of the result of the export, reset to our original branch.
  if ($branch != $original_branch) {
    drush_shell_cd_and_exec($destination_dir, 'git checkout %s', $original_branch);
  }

  return $result;
}

/**
 * Actual config export operation.
 *
 * @param string $destination
 *   Destination param value.
 * @param string $destination_dir
 *   Destination directory path.
 * @param string $branch
 *   Working git branch.
 *
 * @return array|false
 *   Drush command status.
 */
function _config_ignore_collection_drush_config_export($destination, $destination_dir, $branch) {
  $commit = drush_get_option('commit');
  $comment = drush_get_option('message', 'Exported configuration.');
  if (count(glob($destination_dir . '/*')) > 0) {
    // Retrieve a list of differences between the active and target
    // configuration (if any).
    if ($destination == CONFIG_SYNC_DIRECTORY) {
      $target_storage = \Drupal::service('config.storage.sync');
    }
    else {
      $target_storage = new FileStorage($destination_dir);
    }
    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    $comparison_source = $active_storage;

    $config_comparer = new StorageComparer($comparison_source, $target_storage, \Drupal::service('config.manager'));
    if (!$config_comparer->createChangelist()->hasChanges()) {
      return drush_log(dt('The active configuration is identical to the configuration in the export directory (!target).', ['!target' => $destination_dir]), LogLevel::OK);
    }

    drush_print("Differences of the active config to the export directory:\n");
    $change_list = [];
    foreach ($config_comparer->getAllCollectionNames() as $collection) {
      $change_list[$collection] = $config_comparer->getChangelist(NULL, $collection);
    }
    // Print a table with changes in color, then re-generate again without
    // color to place in the commit comment.
    _drush_print_config_changes_table($change_list);
    $tbl = _drush_format_config_changes_table($change_list);
    $output = $tbl->getTable();
    if (!stristr(PHP_OS, 'WIN')) {
      $output = str_replace("\r\n", PHP_EOL, $output);
    }
    $comment .= "\n\n$output";

    if (!$commit && !drush_confirm(dt('The .yml files in your export directory (!target) will be deleted and replaced with the active config.', ['!target' => $destination_dir]))) {
      return drush_user_abort();
    }
    // Only delete .yml files, and not .htaccess or .git.
    $target_storage->deleteAll();
  }

  // Write all .yml files.
  $source_storage = \Drupal::service('config.storage');
  if ($destination == CONFIG_SYNC_DIRECTORY) {
    $destination_storage = \Drupal::service('config.storage.sync');
  }
  else {
    $destination_storage = new FileStorage($destination_dir);
  }

  foreach ($source_storage->listAll() as $name) {
    $destination_storage->write($name, $source_storage->read($name));
  }

  // Export configuration collections.
  foreach (\Drupal::service('config.storage')->getAllCollectionNames() as $collection) {
    $source_storage = $source_storage->createCollection($collection);
    $destination_storage = $destination_storage->createCollection($collection);
    foreach ($source_storage->listAll() as $name) {
      $destination_storage->write($name, $source_storage->read($name));
    }
  }

  drush_log(dt('Configuration successfully exported to !target.', ['!target' => $destination_dir]), LogLevel::SUCCESS);
  drush_backend_set_result($destination_dir);

  // Commit and push, or add exported configuration if requested.
  $remote = drush_get_option('push', FALSE);
  if ($commit || $remote) {
    // There must be changed files at the destination dir; if there are not,
    // then we will skip the commit-and-push step.
    $result = drush_shell_cd_and_exec($destination_dir, 'git status --porcelain .');
    if (!$result) {
      return drush_set_error('DRUSH_CONFIG_EXPORT_FAILURE', dt("`git status` failed."));
    }
    $uncommitted_changes = drush_shell_exec_output();
    if (!empty($uncommitted_changes)) {
      $result = drush_shell_cd_and_exec($destination_dir, 'git add -A .');
      if (!$result) {
        return drush_set_error('DRUSH_CONFIG_EXPORT_FAILURE', dt("`git add -A` failed."));
      }
      $comment_file = drush_save_data_to_temp_file($comment);
      $result = drush_shell_cd_and_exec($destination_dir, 'git commit --file=%s', $comment_file);
      if (!$result) {
        return drush_set_error('DRUSH_CONFIG_EXPORT_FAILURE', dt("`git commit` failed.  Output:\n\n!output", ['!output' => implode("\n", drush_shell_exec_output())]));
      }
      if ($remote) {
        // Remote might be FALSE, if --push was not specified, or
        // it might be TRUE if --push was not given a value.
        if (!is_string($remote)) {
          $remote = 'origin';
        }
        $result = drush_shell_cd_and_exec($destination_dir, 'git push --set-upstream %s %s', $remote, $branch);
        if (!$result) {
          return drush_set_error('DRUSH_CONFIG_EXPORT_FAILURE', dt("`git push` failed."));
        }
      }
    }
  }
  elseif (drush_get_option('add')) {
    drush_shell_exec_interactive('git add -p %s', $destination_dir);
  }

  $values = [
    'destination' => $destination_dir,
  ];
  return $values;
}
