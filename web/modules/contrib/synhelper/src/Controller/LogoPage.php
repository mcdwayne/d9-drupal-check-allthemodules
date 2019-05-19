<?php

namespace Drupal\synhelper\Controller;

/**
 * @file
 * Contains \Drupal\synhelper\Controller\Page.
 */
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class LogoPage extends ControllerBase {

  /**
   * Page Callback.
   */
  public function page() {
    return [
      'logo' => Logo::renderable(),
    ];
  }

}
