<?php

/**
 * @file
 * Prepared Data Drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function prepared_data_drush_command() {
  $items['prepared-data-build'] = [
    'description' => dt('Builds up prepared data.'),
    'callback' => 'drush_prepared_data_build',
    'core' => ['8+'],
    'drupal dependencies' => ['prepared_data'],
    'arguments' => [
      'partial' => dt('(Optional) Either a partial or complete key for providers which need this information to build up the data. Take a look at the documentation of implemented ::nextMatch()'),
    ],
    'options' => [
      'refresh' => dt('Force refreshing of data. Default is not enforced.'),
      'uid' => dt('The user id of the account to use regards data access. Default is set to the permissions of an anonymous user.'),
      'wait' => dt('Microsettings to wait before processing the next record. Default is set to 100000 (0.1 seconds).'),
      'limit' => dt('The maximum number of data-sets to build up. Set to 0 for a non-stop build up. Defaults to 0 (non-stop).'),
      'offset' => dt('The offset to start at for fetching next matches.'),
      'state' => dt('A state ID to use for the process. When using multiple processes, one separate ID per process should be used. The process will continue at its previous state, which will be identified by this ID. Default is set to 1.'),
    ],
    'examples' => [
      'drush prepared-data-build entity:node' => dt('Builds up prepared data for every node.'),
    ],
  ];
  $items['prepared-data-refresh'] = [
    'description' => dt('Refreshes existing records of prepared data.'),
    'callback' => 'drush_prepared_data_refresh',
    'core' => ['8+'],
    'drupal dependencies' => ['prepared_data'],
    'options' => [
      'all' => dt('Force refreshing of every fetched record. By default, only expired and flagged records would be refreshed.'),
      'uid' => dt('The user id of the account to use regards data access. Default is set to the permissions of an anonymous user.'),
      'wait' => dt('Microsettings to wait before processing the next record. Default is set to 100000 (0.1 seconds).'),
      'limit' => dt('The maximum number of data-sets to build up. Set to 0 for a non-stop build up. Defaults to 0 (non-stop).'),
    ],
    'examples' => [
      'drush prepared-data-refresh --limit=100 --uid=1' => dt('Refreshes 100 data records with permissions of user ID 1.'),
    ],
  ];
  return $items;
}

/**
 * Drush command callback to build prepared data.
 *
 * @param string $partial
 *   (Optional) Either a partial or complete key for providers
 *   which need this information to build up the data.
 *   Take a look at the documentation of implemented ::nextMatch()
 *   methods to see which information is needed by certain providers.
 */
function drush_prepared_data_build($partial = NULL) {
  $options = [
    'refresh' => drush_get_option('refresh', FALSE),
    'uid' => drush_get_option('uid', 0),
    'wait' => drush_get_option('wait', 100000),
    'limit' => drush_get_option('limit', 0),
    'offset' => drush_get_option('offset', 0),
    'state' => drush_get_option('state', 1),
  ];
  /** @var \Drupal\prepared_data\Commands\PreparedDataCommands $data_commands */
  $data_commands = \Drupal::service('prepared_data.commands');
  $data_commands->build($partial, $options);
}

/**
 * Drush command callback to refresh prepared data records.
 */
function drush_prepared_data_refresh() {
  $options = [
    'all' => drush_get_option('all', FALSE),
    'uid' => drush_get_option('uid', 0),
    'wait' => drush_get_option('wait', 100000),
    'limit' => drush_get_option('limit', 0),
  ];
  /** @var \Drupal\prepared_data\Commands\PreparedDataCommands $data_commands */
  $data_commands = \Drupal::service('prepared_data.commands');
  $data_commands->refresh($options);
}
