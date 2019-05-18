<?php

/**
 * @file
 * Contains \Drupal\mailjet\Controller\MailjetMyAccountController.
 */

namespace Drupal\mailjet\Controller;

use Drupal\Core\Controller\ControllerBase;

class MailjetMyAccountController extends ControllerBase {

  public function redirect_my_profile() {
    define("IFRAME_URL", "https://app.mailjet.com/");
    return mailjet_go_to_external_link(IFRAME_URL . 'account');
  }
}