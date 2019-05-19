<?php

namespace Drupal\watchdog_event_extras;

/**
 * An interface for all WEE type plugins.
 */
interface WEEInterface {

  /**
   * Provide the title of the WEE.
   *
   * @return string
   *   A string title of the WEE
   */
  public function title();

  /**
   * Add stuff to the '#attached' key in the table render array.
   */
  public function attached(&$attached, $dblog);

  /**
   * Provide the content of the WEE.
   *
   * @return string
   *   A string content of the WEE
   */
  public function markup($dblog);

}
