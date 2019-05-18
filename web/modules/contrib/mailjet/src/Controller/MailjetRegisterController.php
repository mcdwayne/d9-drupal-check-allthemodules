<?php
/**
 * @file
 * Contains \Drupal\mailjet\Controller\MailjetRegisterController.
 */

namespace Drupal\mailjet\Controller;

use Drupal\Core\Controller\ControllerBase;

class MailjetRegisterController extends ControllerBase {

  public function redirect_register() {
    define("IFRAME_URL", "https://app.mailjet.com/");
    return mailjet_go_to_external_link(IFRAME_URL . 'signup?aff=drupal-3.0');
  }
}
