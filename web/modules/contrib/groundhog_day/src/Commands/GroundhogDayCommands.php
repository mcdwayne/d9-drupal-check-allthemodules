<?php

namespace Drupal\groundhog_day\Commands;

use Drupal\groundhog_day\GroundhogDayExecutor;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class GroundhogDayCommands extends DrushCommands {

  /**
   * @var \Drupal\groundhog_day\GroundhogDayExecutor
   */
  protected $executor;

  public function __construct(GroundhogDayExecutor $executor) {
    $this->executor = $executor;
  }


  /**
   * 
   *
   *
   * @command groundhog-day:update
   * @aliases ghdu,groundhog-day-update
   */
  public function update() {
    $this->executor->update();
  }

  /**
   * 
   *
   *
   * @command groundhog-day:reset
   * @aliases ghdr,groundhog-day-reset
   */
  public function reset() {
    $this->executor->reset();
  }

}
