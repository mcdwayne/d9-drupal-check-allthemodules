<?php
/**
 * @file
 * Contains \Drupal\mailjet\Controller\MailjetUpgradeController.
 */

namespace Drupal\mailjet\Controller;

use Drupal\Core\Controller\ControllerBase;

class MailjetUpgradeController extends ControllerBase {

  public function redirect_upgrade() {
    define("IFRAME_URL", "https://app.mailjet.com/");
    return mailjet_go_to_external_link(IFRAME_URL . 'pricing');
  }
}