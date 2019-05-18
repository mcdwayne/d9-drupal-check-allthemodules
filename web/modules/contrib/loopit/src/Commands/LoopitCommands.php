<?php
namespace Drupal\loopit\Commands;

use Drush\Commands\DrushCommands;

class LoopitCommands extends DrushCommands {

  /**
   * Compare overrided Plugin/Service methods on core/contrib updates.
   *
   * @command method-overrides
   * @aliases m-ovrd
   */
  public function methodOverrides() {


    require_once drupal_get_path('module', 'loopit') . '/loopit.drush.inc';
    drush_loopit_method_overrides();
  }

}