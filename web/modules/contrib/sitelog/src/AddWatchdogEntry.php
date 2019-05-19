<?php

namespace Drupal\sitelog;

/**
 * Add watchdog entry for logged site data.
 */
class AddWatchdogEntry {

  /**
   * Adds entry.
   */
  public function add($type) {
    $short = \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short');
    $notice = t('Site %type data logged at ', array(
      '%type' => $type,
    )) . $short;
    \Drupal::logger('sitelog')->notice($notice);
  }
}
