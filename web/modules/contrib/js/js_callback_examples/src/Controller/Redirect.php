<?php

namespace Drupal\js_callback_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the js_callback_examples module.
 */
class Redirect extends ControllerBase {

  /**
   * The content for this controller.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function content() {
    return $this->redirect('js_callback_examples.redirect_result');
  }

}
