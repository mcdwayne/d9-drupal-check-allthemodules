<?php

namespace Drupal\watchdog_event_extras\Plugin\WEE;

use Drupal\watchdog_event_extras\WEEBase;
use Drupal\user\Entity\User;

/**
 * Provides a 'test' wee.
 *
 * @WEE(
 *   id = "sfs_wee",
 *   title = @Translation("Stop Forum Spam"),
 * )
 */
class SfsWee extends WEEBase {

  /**
   * {@inheritdoc}
   */
  public function attached(&$attached, $dblog) {
    if ($dblog->hostname != '127.0.0.1' && $dblog->hostname != '::1') {
      $attached['library'][] = 'watchdog_event_extras/wee.sfs';
      $attached['drupalSettings']['wee']['hostname'] = $dblog->hostname;

      if ($dblog->uid > 1) {
        $account = User::load($dblog->uid);
        $attached['drupalSettings']['wee']['username'] = $account->getUsername();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function markup($dblog) {
    if ($dblog->hostname != '127.0.0.1' && $dblog->hostname != '::1') {
      return '<div id="event-sfs" class=""></div>';
    }
    else {
      return '<div id="event-sfs-localhost" class="">Localhost ' . $dblog->hostname . '</div>';
    }
  }

}
