<?php

namespace Drupal\drush_progress\Commands;

use Drush\Commands\DrushCommands;

/**
 * Command file for Drush Progress commands.
 */
class DrushProgressCommands extends DrushCommands {

  /**
   * Test that the progress bar works.
   *
   * @usage drush progress:test
   *
   * @command progress:test
   */
  public function progressTest() {
    $bar = drush_progress_bar();
    $total = 1000;
    for ($i = 0; $i < $total; ++$i) {
      $bar->setProgress($total, $i);
      usleep(1000);
    }
    $bar->end();
  }

}
