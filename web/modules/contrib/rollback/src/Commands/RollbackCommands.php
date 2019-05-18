<?php

namespace Drupal\rollback\Commands;

use Drush\Commands\DrushCommands;
use Drupal\rollback\Rollback;

/**
 * Class RollbackCommands.
 */
class RollbackCommands extends DrushCommands {

  /**
   * Implements the state system.
   *
   * @var Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Implements the rollback.
   *
   * @var Drupal\rollback\Rollback
   */
  protected $rollback;

  /**
   * Construct a new RollbackCommands object.
   */
  public function __construct() {
    $this->state = \Drupal::service('state');
    $this->rollback = \Drupal::service('rollback.rollback');
    parent::__construct();
  }

  /**
   * Rollback a rollback compatible database update.
   *
   * @param string $module
   *   The machine name of the module.
   * @param int $schema
   *   The schema version to revert.
   *
   * @usage drush rollbackdb views 8101
   *   - Reverts the 8101 database update for the views module.
   *
   * @command rollbackdb
   * @aliases rbdb
   */
  public function rollbackdb($module, $schema) {
    // Place the site in to maintenance mode while the update is
    // rolled back.
    $this->state->set('system.maintenance_mode', TRUE);
    $this->logger()->info(dt('Maintenance mode is now enabled'));

    // Run the rollback for the specified module and schema.
    $rollbacks = $this->rollback->run($module, $schema);

    if (is_array($rollbacks)) {
      foreach ($rollbacks as $update) {
        $this->logger()->success(dt('Rolled back ' . unserialize($update->target) . ' @ schema ' . $update->schema_version));
      }
    }
    elseif (!$rollbacks) {
      $this->logger()->error(dt('No updates available to rollback'));
    }

    // Take the site out of maintenance mode.
    $this->state->set('system.maintenance_mode', FALSE);
    $this->logger()->info(dt('Maintenance mode is now disabled'));

    if (is_array($rollbacks)) {
      drush_drupal_cache_clear_all();
    }
  }

}
