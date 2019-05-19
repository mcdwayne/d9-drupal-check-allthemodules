<?php

namespace Drupal\simple_a_b\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Used to create a blank page, which a view will attach too.
 *
 * @see /admin/config/user-interface/simple-a-b
 */
class SimpleABTestController extends ControllerBase {

  /**
   * Returns an empty output.
   *
   * This creates a blank page for a view to be
   * attached too.
   *
   * @return array
   *   Returns the output of display.
   */
  public function createViewTestsPage() {

    $output = [];

    return $output;
  }

}
