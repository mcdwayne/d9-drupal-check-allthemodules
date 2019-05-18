<?php

namespace Drupal\onelogin_integration;

/**
 * Interface AuthenticationServiceInterface.
 *
 * The interface for the AuthenticationService.
 * Defines methods that should be used when the interface is implemented.
 *
 * @package Drupal\onelogin_integration
 */
interface AuthenticationServiceInterface {

  /**
   * The processLoginRequest method.
   *
   * Gets the username or email address from the OneLogin request and calls
   * other functions accordingly.
   *
   * @return mixed
   *   Returns multiple things, depending on the part of the code that is
   *   executing.
   */
  public function processLoginRequest();

  /**
   * The syncRoles method.
   *
   * Takes the some SAML attributes from the processLoginRequest() method
   * to decide which roles should be synced.
   *
   * @param object $user
   *   The user object.
   * @param array $saml_attributes
   *   Attributes from the OneLogin request. Coming from the code in the
   *   processLoginRequest() method.
   *
   * @return array
   *   Returns an (updated) user object or a RedirectResponse.
   */
  public function syncRoles($user, array $saml_attributes);

  /**
   * The autocreateUser method.
   *
   * Creates a new user for the website.
   *
   * @param string $username
   *   The username of the person to create.
   * @param string $email
   *   The email of the person to create.
   * @param array $saml_attributes
   *   The attributes from the OneLogin request for the roles to check.
   *
   * @return object
   *   Returns a user object.
   */
  public function autocreateUser($username, $email, array $saml_attributes);

}
