<?php

namespace Drupal\drd_migrate\Commands;

use Drush\Commands\DrushCommands;

/**
 * Class Base.
 *
 * @package Drupal\drd
 */
class Drush extends DrushCommands {

  /**
   * Configure this domain for communication with a DRD instance.
   *
   * @param string $inventory
   *   Filename containing the json with you DRD 7 inventory.
   *
   * @command drd:migratefromd7
   * @aliases drd-migrate-from-d7
   */
  public function migrate($inventory) {
    \Drupal::service('drd_migrate.import')->execute($inventory);
  }

}
