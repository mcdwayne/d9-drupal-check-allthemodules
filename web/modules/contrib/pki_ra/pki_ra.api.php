<?php

/**
 * @file
 * API and hook documentation for the PKI RA module.
 */

/**
 * Alter EOI Sources.
 * EOI - Evidence of Identity
 *
 * Any module willing to add more EOI Sources to the existing
 * Methods should implement this hook.
 *
 * @param array $methods
 *   And array of methods which are already added in this module.
 * @return array
 *  An array of EOI Sources.
 */
function hook_pki_ra_eoi_sources_alter(&$methods) {
  // Dummy method.
  $methods['phone_verification'] = [
    'weight' => 3,
    'label' => t('Phone Verification'),
    'url' => 'url-of-method' ? : '#',
    'options' => [
      'required' => t('Required'),
      'enabled' => t('Enabled'),
      'disabled' => t('Disabled'),
    ],
  ];
  return $methods;
}

/**
 * Alters the data in the CSR sent to the CA.
 *
 * Any module wanting to alter the data sent to CSR should implement this hook.
 *
 * @param $data
 * @return array
 */
function hook_pki_ra_csr_data_alter(&$data) {
  // Update these values with actual one.
  $data['registration_id'] = 1;
  $data['title'] = t('Lorem Ipsum');
  return $data;
}

/**
 * Alters the email message sent for email verification.
 *
 * Any module wanting to alter the email message should implement this hook.
 *
 * @param array $parameters
 *   Contains the url sent to the email or registrant for verification.
 *
 * @return array
 *   An array with the altered parameter value.
 */
function hook_pki_ra_email_verification_message_alter(&$parameters) {
  // Custom logic to change the verification link text for email verification.
  // The verification link contains the registration ID as well as the token.
  // If only token is required to be sent during email verification.
  // Use following code.
  $explode = explode('/', $parameters['url']);
  $parameters['url'] = end($explode);

  return $parameters;
}
