<?php

namespace Drupal\key_auth_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Test controller for key authentication.
 */
class KeyAuthTestController extends ControllerBase {

  /**
   * Print the current user's name to the page.
   *
   * @return string
   *   The user name of the current logged in user.
   */
  public function test() {
    $account = $this->currentUser();
    return ['#markup' => $account->getUsername() . ' - ' . round(microtime(TRUE) * 1000)];
  }

}
