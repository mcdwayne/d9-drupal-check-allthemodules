<?php

namespace Drupal\widencollective;

/**
 * Class WidencollectiveAuthService.
 *
 * @package Drupal\widencollective
 */
class WidencollectiveAuthService implements WidencollectiveAuthServiceInterface {

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * Returns widen setting config where it stores the authentication data.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   An immutable configuration object.
   */
  public static function getConfig() {
    return \Drupal::config('widencollective.settings');
  }

  /**
   * Gets endpoint or path.
   *
   * @param string $method
   *   The method to be called in the API.
   *
   * @return string
   *   The absolute path of the endpoint of the method.
   */
  public static function getEndpoint($method) {
    // Generate the endpoint SSL URL of the given method.
    $config = self::getConfig();
    $collective_domain = $config->get('collective_domain');

    if (isset($collective_domain)) {
      return 'https://' . $collective_domain . '/api/rest/' . $method;
    }

    drupal_set_message(t('Widen Collective endpoint must be configured'), 'error');
  }

  /**
   * Provides the authorization link with Widen Collective.
   *
   * @param string $return_link
   *   The url where it should redirect after the authentication.
   *
   * @return string
   *   The absolute URL used for authorization.
   */
  public static function generateAuthUrl($return_link) {
    $config = self::getConfig();
    $collective_domain = $config->get('collective_domain');
    $clientRegistration = $config->get('client_registration');

    return 'https://' . $collective_domain . '/allowaccess?client_id=' . $clientRegistration . '&redirect_uri=' . $return_link;
  }

  /**
   * Purge widen authorization connection.
   *
   * @param string $access_token
   *   Widen user token.
   *
   * @return bool
   *   Returns boolean based on access authorization.
   */
  public static function cancel($access_token) {
    if (empty($access_token)) {
      drupal_set_message(t('No token was provided.'), 'error');
      return FALSE;
    }

    $endpoint = self::getEndpoint('oauth/logout');

    // Initiate and process the response of the HTTP request.
    $response = \Drupal::httpClient()
      ->post($endpoint, [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);

    $http_status = $response->getStatusCode();

    // Display an error message if request fail.
    if ($http_status != '200') {
      $error_msg = t('Error Response from Authorization call [@status]', ['@status' => $http_status]);
      drupal_set_message($error_msg, 'error');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Authenticates the user.
   *
   * @param string $auth_code
   *   The authorization code.
   *
   * @return array
   *   The response data of the authentication attempt.
   */
  public static function authenticate($auth_code) {
    // Generate the token endpoint SSL URL of the request.
    $endpoint = self::getEndpoint('oauth/token');

    $data = [
      'authorization_code' => $auth_code,
      'grant_type' => 'authorization_code',
    ];

    $config = self::getConfig();
    $clientRegistration = $config->get('client_registration');
    $clientHash = $config->get('client_hash');

    // Initiate and process the response of the HTTP request.
    $response = \Drupal::httpClient()
      ->post($endpoint, [
        'auth' => [$clientRegistration, $clientHash],
        'body' => json_encode($data),
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);

    $http_status = $response->getStatusCode();

    // Display an error message if request fail.
    if ($http_status != '200') {
      $error_msg = t('Error Response from Authorization call [@status]', ['@status' => $http_status]);
      drupal_set_message($error_msg, 'error');
    }

    return json_decode($response->getBody());
  }

}
