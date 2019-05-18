<?php

/**
 * @file
 * User pages for the SAML Service Provider module.
 */

use OneLogin\Saml2\Auth;

/**
 * Check that a request is a valid SAML authentication response.
 */
function saml_sp__is_valid_authentication_response() {
  return ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['SAMLResponse']));
}

/**
 * Page callback to initiate the SAML SLO process.
 */
function saml_sp__logout() {
  // Load the IdP to authenticate against.
  $idp = saml_sp_drupal_login__get_idp();

  // Settings is a \OneLogin\Saml2\Settings object.
  $settings = saml_sp__get_settings($idp);

  $auth = new Auth($settings);
  // Handle SAML Request / Response and process Single LogOut.
  $auth->processSLO();

  $errors = $auth->getErrors();
  if (empty($errors)) {
    \Drupal::logger('saml_sp')->notice('Handled SLO Request/Response from SSO.');
  }
  else {
    \Drupal::logger('saml_sp')->error('Errors in SLO process: %errors', ['%errors' => implode(', ', $errors)]);
  }
  // Redirect user to path defined in RelayState after logout, or to front page
  // if it's empty.
  \Drupal::messenger()->addMessage(t('You have successfully logged out from all SSO services.'));
  drupal_goto($_GET['RelayState']);
}
