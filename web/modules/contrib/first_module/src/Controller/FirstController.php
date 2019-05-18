<?php

namespace Drupal\first_module\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Manage Content.
 */
class FirstController extends ControllerBase {

  /**
   * To display "Hello world Page Content" on page.
   *
   * @return array
   *   Returns content for page.
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => t('Hello world Page Content'),
    ];
  }

}
