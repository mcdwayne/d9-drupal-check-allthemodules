<?php

/**
 * @file
 * Contains SubjectInterface.php.
 */

namespace Drupal\changed_fields;

use SplObserver;

/**
 * Interface ObserverInterface.
 */
interface ObserverInterface extends SplObserver {

  /**
   * Returns associative array of node types with their fields for watching.
   *
   * @return array
   */
  public function getInfo();

}
