<?php

/**
 * @file
 * Contains \Drupal\page_example\Controller\PageExampleController.
 */

namespace Drupal\gmail_contact\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines pages.
 */
class GmailContactController extends ControllerBase {
  public function initiate_invite() {
    if (isset($_GET["code"])) {
      // Store auth code in session.
      $_SESSION['gmail_auth_code'] = $_GET['code'];

      // Redirect to gmail invite form.
      return $this->redirect('gmail-invite');
    }
    else {
      return array(
        '#markup' => t('You are not supposed to be on this page.')
      );
    }
  }
}

?>