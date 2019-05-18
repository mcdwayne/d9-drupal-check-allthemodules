<?php

namespace Drupal\onelogin_integration;

/**
 * Interface SamlAuthenticatorServiceInterface.
 *
 * @package Drupal\onelogin_integration
 */
interface SAMLAuthenticatorFactoryInterface {

  /**
   * Creates and/or returns in instance of the Auth library.
   *
   * @param array $settings
   *   returns in instance of the Auth library.
   *
   * @return \OneLogin\Saml2\Auth
   *   Return instance of Auth.
   */
  public function createFromSettings(array $settings = []);

}
