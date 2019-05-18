<?php
use Drupal\Core\Config\FileStorage;
use Drush\Log\LogLevel;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Component\Serialization\Yaml;

/**
 * @file
 * Drush integration for the config_partial_export module.
 */

/**
 * Implements hook_drush_command().
 */
function config_partial_export_drush_command() {
  $items['config-partial-export'] = array(
    'description' => 'Export a partial configuration to a destination.',
    'core' => array('8+'),
    'aliases' => array('cpex'),
    'arguments' => array(
      'config' => "Configuration keys, comma separated",
      'label' => "A config destination label (i.e. a key in \$config_directories array in settings.php). Defaults to 'sync'",
    ),
    'options' => array(
      'changelist' => 'Shows the list of changed active config.',
      'show-destinations' => array(
        'description' => 'Choose from a list of config destinations',
      ),
    ),
    'required-arguments' => 1,
    'examples' => array(
      'drush cpex core.date_format.long,core.extensions' => 'Export configuration to default config destination, likely "sync".',
      'drush cpex core.date_format.long,core.extensions secondary' => 'Export configuration to "secondary" config destination.',
      'drush cpex core.date_format.long,core.extensions --show-destinations' => 'Export configuration and select the destination.',
      'drush cpex config --changelist' => 'List the recent changes in configuration.',
    ),
  );
  return $items;
}

/**
 * Drush command callback to perform a partial config export.
 *
* @param string $config
*   Comma separated list of specific configuration keys.
* @param string $label
*   Optional config directory key.
*
* @return bool
*   Success of writing the partial config to the destination.
*/
function drush_config_partial_export($config, $label = NULL) {
  $changelist = drush_get_option('changelist', 0);
  if ($changelist) {
    $changes = _config_partial_export_get_changes();
    if (!empty($changes)) {
      drush_print(dt("Your configuration has changed:"));
      foreach ($changes as $key => $values) {
        drush_print($key);
        foreach ($values as $value) {
          drush_print('- ' . $value);
        }
      }
    }
    return TRUE;
  }
  global $config_directories;
  $choices = drush_map_assoc(array_keys($config_directories));
  unset($choices[CONFIG_ACTIVE_DIRECTORY]);
  // Throw a warning if someone wants to show destinations but supplied one.
  if (!empty($label) and drush_get_option('show-destinations')) {
    drush_log('Error, supplied both a destination and the list. Using the supplied destination and ignoring the list',
    LogLevel::ERROR);
  }
  // List out the destinations to select.
  elseif (drush_get_option('show-destinations')) {
    $label = drush_choice($choices, 'Choose a destination.');
    if (empty($label)) {
      return drush_user_abort();
    }
  }

  // Check to see if destination is still undefined, set it to default.
  if (empty($label)) {
    $label = CONFIG_SYNC_DIRECTORY;
  }

  // Check if label doesn't exist.
  if (!in_array($label, $choices)) {
    $msg = dt('Error !target not found as a configuration target.',
      ['!target' => $label]);
    return drush_set_error('NO_CONFIG_DEST', $msg);
  }

  $destination_dir = config_get_config_directory($label);
  $destination_storage = new FileStorage($destination_dir);
  $source_storage = \Drupal::service('config.storage');

  $config_keys = explode(",", $config);
  foreach ($config_keys as $config_key) {
    // Look for a wildcard character.
    if (strpos($config_key, '*') !== FALSE) {
      $wildcard_keys = _config_partial_export_get_wildcard_keys($config_key, $source_storage);
      foreach ($wildcard_keys as $wildcard_key) {
        _config_partial_export_write_config($wildcard_key, $source_storage, $destination_storage, $destination_dir);
      }
    }
    else {
      _config_partial_export_write_config($config_key, $source_storage, $destination_storage, $destination_dir);
    }
  }

  return TRUE;
}

/**
 * Writes a YAML configuration file to the specified directory.
 *
 * @param string $key
 *   Configuration key.
 * @param \Drupal\Core\Config\StorageInterface $source_storage
 *   The source storage.
 * @param \Drupal\Core\Config\StorageInterface $destination_storage
 *   The source storage.
 *
 * @return bool
 *   Whether or not the configuration was moved from source to destination.
 */
function _config_partial_export_write_config($key, StorageInterface $source_storage, StorageInterface $destination_storage, $destination_dir) {
  $data = $source_storage->read($key);
  // New config.
  if (empty($data)) {
    $data = \Drupal::service('config.manager')->getConfigFactory()->get($key)->getRawData();
  }
  $destination_storage->write($key, $data);
  drush_log(dt('Writing !name to !target.', array(
    '!name' => $key,
    '!target' => $destination_dir,
  )), LogLevel::SUCCESS);

  return $data;
}

/**
 * Checking if a configuration matches a wildcard.
 *
 * @param string $input
 *   The string that contains the wildcard.
 * @param \Drupal\Core\Config\StorageInterface $storage
 *   The source storage.
 *
 * @return array
 *   The list of keys.
 */
function _config_partial_export_get_wildcard_keys($input, StorageInterface $storage) {
  // Get the strings around the wildcard.
  $split = explode('*', $input);
  $matching_keys = [];

  // Load the possible keys that start with the first prefix.
  $possible_keys = $storage->listAll($split[0]);

  // Check each key if they match the strings.
  foreach ($possible_keys as $config_key) {
    $match = TRUE;
    $counter = strlen($split[0]);

    for ($i = 1; $i < count($split); $i++) {
      if (!empty($split[$i])) {
        // Check if the partial exists after the last check.
        $pos = strpos($config_key, $split[$i], $counter);
        // If no "match" was found for this partial, it should fail.
        if ($pos === FALSE) {
          $match = FALSE;
        }
        // Increment the counter by the position found and length of the match.
        $counter += ($pos + strlen($split[$i]));
      }
    }
    if ($match) {
      $matching_keys[] = $config_key;
    }
  }
  return $matching_keys;
}

/**
 * Gets the list of changed configurations.
 *
 * @return array|bool
 *   TRUE if there are no changes.
 */
function _config_partial_export_get_changes() {
  $sync_storage = \Drupal::service('config.storage.sync');
  $active_storage = \Drupal::service('config.storage');
  $config_manager = \Drupal::service('config.manager');

  $storage_comparer = new StorageComparer($sync_storage, $active_storage, $config_manager);

  $source_list = $sync_storage->listAll();
  $change_list = $storage_comparer->createChangelist();
  if (empty($source_list) || !$change_list->hasChanges()) {
    drush_print(dt('There are no configuration changes.'));
    return TRUE;
  }
  $diff = $change_list->getChangelist();
  if (!empty($diff)) {
    foreach ($diff as $action => $config_names) {
      if (empty($config_names)) {
        unset($diff[$action]);
        continue;
      }
      sort($diff[$action]);
    }
  }

  return $diff;
}
