<?php

namespace Drupal\revue;

/**
 * Interface RevueApiInterface.
 *
 * @package Drupal\revue
 */
interface RevueApiInterface {

  const REVUE_API_URL = 'https://www.getrevue.co/api/v2';
  const REVUE_API_SUBSCRIBERS_PATH = '/subscribers';
  const REVUE_API_ACCOUNT_PATH = '/accounts/me';

  /**
   * Subscribe to the Revue newsletter for the given API key.
   *
   * @param string $api_key
   *   The Revue API key.
   * @param string $email
   *   The email adress to subscribe.
   * @param string $first_name
   *   The first name of the subscriber (optional).
   * @param string $last_name
   *   The last name of the subscriber (optional).
   *
   * @throws \Drupal\revue\RevueApiException
   */
  public function subscribe($api_key, $email, $first_name = '', $last_name = '');

  /**
   * Gets the Revue profile url for the given API key.
   *
   * @param string $api_key
   *   The Revue API key.
   *
   * @throws \Drupal\revue\RevueApiException
   *
   * @return string
   *   The Revue profile url.
   */
  public function getProfileUrl($api_key);

}
