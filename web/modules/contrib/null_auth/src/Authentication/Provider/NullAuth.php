<?php

namespace Drupal\null_auth\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Null authentication provider.
 */
class NullAuth implements AuthenticationProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // Only apply this validation if request has _null_auth query parameter sets
    // to 1.
    $null_auth = (bool) $request->get('_null_auth');

    if ($null_auth) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // Return anonymous user.
    return User::getAnonymousUser();
  }

}

