<?php
/**
 * Contains NotifierInterface.php.
 */

namespace Drupal\notifier_scc\CurrencyConverterNotifier;

interface NotifierInterface {

  /**
   * Processes a notification.
   *
   * @param $data
   *
   * @return mixed
   */
  public function notify($data);

}
