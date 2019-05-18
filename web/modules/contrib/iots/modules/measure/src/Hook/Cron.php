<?php

namespace Drupal\iots_measure\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * Hook Cron.
 */
class Cron extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook() {
    \Drupal::logger('iots-measure')->notice('cron run');
  }

}
