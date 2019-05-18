<?php

/**
 * @file
 * API hooks for SAML Service Provider module.
 */

use OneLogin\Saml2\Settings;

/**
 * Alter the settings used when generating SAML requests.
 *
 * @param \OneLogin\Saml2\Settings $settings
 *   The Settings object.
 */
function hook_saml_sp_settings_alter(Settings &$settings) {
  // Change the consuming URL to a custom endpoint.
  if ($settings->login_url == 'http://example.com/saml/foo') {
    $settings->spReturnUrl = url('saml/custom_action', ['absolute' => TRUE]);
  }
}
