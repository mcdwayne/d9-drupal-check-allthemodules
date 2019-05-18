<?php

namespace Drupal\drd_pi\Commands;

use Drupal\drd\Commands\DrdCommands;

/**
 * Class Base.
 *
 * @package Drupal\drd
 */
class DrdPiCommands extends DrdCommands {

  /**
   * Sync DRD inventory with all configured platforms.
   *
   * @command drd:pi:sync
   * @aliases drd-pi-sync
   */
  public function projectsStatus() {
    $this->actionKey = 'drd_action_pi_sync';
    $this
      ->prepare()
      ->execute();
  }

}
