<?php

namespace Drupal\prepared_data\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commands regards Prepared Data.
 */
class PreparedDataDrushCommands extends DrushCommands {

  /**
   * The service implementation of data commands.
   *
   * @var \Drupal\prepared_data\Commands\PreparedDataCommands
   */
  protected $dataCommands;

  /**
   * PreparedDataDrushCommands constructor.
   *
   * @param \Drupal\prepared_data\Commands\PreparedDataCommands $data_commands
   *   The service implementation of data commands.
   */
  public function __construct(PreparedDataCommands $data_commands) {
    $this->dataCommands = $data_commands;
  }

  /**
   * Builds up prepared data.
   *
   * @param string $partial
   *   (Optional) Either a partial or complete key for providers
   *   which need this information to build up the data.
   *   Take a look at the documentation of implemented ::nextMatch()
   *   methods to see which information is needed by certain providers.
   * @param array $options
   *   (Optional) An array of options. See below which options are available.
   *
   * @command prepared-data:build
   *
   * @usage drush prepared-data:build entity:node
   *   Builds up prepared data for every node.
   *
   * @option refresh
   *   Force refreshing of data. Default is not enforced.
   * @option uid
   *   The user id of the account to use regards data access.
   *   Default is set to the permissions of an anonymous user.
   * @option wait
   *   Microsettings to wait before processing the next record.
   *   Default is set to 100000 (0.1 seconds).
   * @option limit
   *   The maximum number of data-sets to build up. Set to 0 for
   *   a non-stop build up. Default is set to 0 (non-stop).
   * @option offset
   *   The offset to start at for fetching next matches.
   *   Default is set to 0 (beginning).
   * @option state
   *   A state ID to use for the process. When using multiple processes,
   *   one separate ID per process should be used. The process will continue
   *   at its previous state, which will be identified by this ID.
   *   Default is set to 1.
   */
  public function build($partial = NULL, array $options = []) {
    $options += [
      'refresh' => FALSE,
      'uid' => 0,
      'wait' => 100000,
      'limit' => 0,
      'offset' => 0,
      'state' => 1,
    ];

    $this->dataCommands->build($partial, $options);
  }

  /**
   * Refreshes existing records of prepared data.
   *
   * @param array $options
   *   (Optional) An array of options. See below which options are available.
   *
   * @command prepared-data:refresh
   *
   * @usage drush prepared-data:refresh --limit=100 --uid=1
   *   Refreshes 100 data records with permissions of user ID 1.
   *
   * @option all
   *   Force refreshing of every fetched record.
   *   By default, only expired and flagged records would be refreshed.
   * @option uid
   *   The user id of the account to use regards data access.
   *   Default is set to the permissions of an anonymous user.
   * @option wait
   *   Microsettings to wait before processing the next record.
   *   Default is set to 100000 (0.1 seconds).
   * @option limit
   *   The maximum number of data-sets to refresh. Set to 0 for
   *   a non-stop refresh. Defaults to 0 (non-stop).
   */
  public function refresh(array $options = []) {
    $options += [
      'all' => FALSE,
      'uid' => 0,
      'wait' => 100000,
      'limit' => 0,
    ];

    $this->dataCommands->refresh($options);
  }

}
