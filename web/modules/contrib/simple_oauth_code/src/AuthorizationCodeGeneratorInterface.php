<?php

namespace Drupal\simple_oauth_code;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface AuthorizationCodeGeneratorInterface.
 */
interface AuthorizationCodeGeneratorInterface {
  public function generate($client, AccountInterface $user);
}
