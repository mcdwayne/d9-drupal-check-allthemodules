<?php

namespace Drupal\changed_fields;

use SplObserver;

/**
 * Interface ObserverInterface.
 */
interface ObserverInterface extends SplObserver {

  /**
   * Returns associative array of entity types with their bundles and fields for watching.
   *
   * @return array
   */
  public function getInfo();

}
