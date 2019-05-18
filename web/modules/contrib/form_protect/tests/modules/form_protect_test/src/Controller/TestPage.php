<?php

/**
 * @file
 * Contains \Drupal\form_protect_test\Controller\TestPage.
 */

namespace Drupal\form_protect_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\form_protect_test\Form\TestForm;

class TestPage extends ControllerBase {

  /**
   * Provides the content callback for form_protect_test.page route.
   */
  public function content() {
    return [
      $this->formBuilder()->getForm(new TestForm(1)),
      $this->formBuilder()->getForm(new TestForm(2)),
    ];
  }

}
