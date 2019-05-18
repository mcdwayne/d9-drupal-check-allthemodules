<?php

namespace Drupal\jscrollpane\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for JscrollPanePage Sample Page.
 */
class JscrollPanePage extends ControllerBase {

  /**
   * Render JscrollPane Sample Page.
   */
  public function demo() {

    return [
      '#theme' => 'jscrollpane_sample',
    ];
  }

}
