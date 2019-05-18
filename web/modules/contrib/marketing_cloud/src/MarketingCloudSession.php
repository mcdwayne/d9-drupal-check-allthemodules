<?php

namespace Drupal\marketing_cloud;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

/**
 * Class MarketingCloudSession.
 *
 * This class is used to maintain the Marketing Cloud token
 * The public points of entry are:
 *   resetToken()
 *     Clear the existing token - this is used if somehow the token becomes
 *     stuck and cannot be refreshed.
 *   token(FALSE)
 *     Fetch the existing token, or a create a new one if it is stale.
 *
 * @package Drupal\marketing_cloud
 */
class MarketingCloudSession {

  private $config;

  /**
   * MarketingCloudSession constructor.
   */
  public function __construct() {
    $this->config = \Drupal::configFactory()
      ->getEditable('marketing_cloud.settings');
  }

  /**
   * Reset the stored token.
   */
  public function resetToken() {
    $this->config
      ->set('token', FALSE)
      ->set('requesting_token', FALSE)
      ->save();
  }

  /**
   * Create and store token for session.
   *
   * @param bool $forceLogin
   *   TRUE = force fetching of a new token.
   *   FALSE = use existing token or fetch a fresh one of stale.
   *
   * @return bool|null
   *   The token or FALSE on failure.
   */
  public function token($forceLogin = FALSE) {
    if ($this->config->get('requesting_token') == TRUE) {
      // Wait n seconds to prevent overloading token request with simultaneous
      // requests.
      sleep($this->config
        ->get('requestToken_wait'));
    }

    $token = $this->config
      ->get('token');
    if ($token != FALSE && !$forceLogin) {
      return $token;
    }

    // Prevent flooding by setting requesting_token to TRUE.
    $this->config
      ->set('requesting_token', TRUE)
      ->save();

    $token = FALSE;

    // Validate required params for token request.
    $token_requisites = TRUE;
    $clientId = $this->config
      ->get('client_id');
    if (empty($clientId)) {
      \Drupal::logger(__METHOD__)->error('Bad config data: %missingData', ['%missingData' => 'client_id']);
      $token_requisites = FALSE;
    }
    $clientSecret = $this->config
      ->get('client_secret');
    if (empty($clientSecret)) {
      \Drupal::logger(__METHOD__)->error('Bad config data: %missingData', ['%missingData' => 'client_secret']);
      $token_requisites = FALSE;
    }
    $url = $this->config
      ->get('request_token_url');
    if (empty($url)) {
      \Drupal::logger(__METHOD__)->error('Bad config data: %missingData', ['%missingData' => 'request_token_url']);
      $token_requisites = FALSE;
    }

    // Make token request/s.
    if ($token_requisites) {
      $loginAttempts = 0;
      $loginAttemptsMax = $this->config->get('login_attempts_max');
      while (!$token && $loginAttempts++ < $loginAttemptsMax) {
        $token = $this->requestToken($url, $clientId, $clientSecret);
      }
    }

    $this->config
      ->set('token', $token)
      ->set('requesting_token', FALSE)
      ->save();

    return $token;
  }

  /**
   * Perform the API call to request a valid token.
   *
   * @param string $url
   *   The Marketing Cloud token request URL.
   * @param string $clientId
   *   The marketing Cloud client ID.
   * @param string $clientSecret
   *   The Marketing Cloud secret.
   *
   * @return bool|string
   *   The result of the token request, or FALSE on failure.
   */
  private function requestToken($url, $clientId, $clientSecret) {
    try {
      \Drupal::logger(__METHOD__)->info('%message', ['%message' => 'Fetching a new token.']);
      $response = \Drupal::httpClient()->post($url, [
        'verify' => FALSE,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'form_params' => [
          'clientId' => $clientId,
          'clientSecret' => $clientSecret,
        ],
      ]);
      $data = json_decode($response->getBody());
      return $data->accessToken;
    }
    catch (RequestException $e) {
      \Drupal::logger(__METHOD__)->error('Request exception, failed to fetch token: %error', ['%error' => $e->getMessage()]);
      return FALSE;
    }
    catch (ClientException $e) {
      \Drupal::logger(__METHOD__)->error('Client exception, failed to fetch token: %error', ['%error' => $e->getMessage()]);
      return FALSE;
    }
    catch (\Exception $e) {
      \Drupal::logger(__METHOD__)->error('Generic exception, failed to fetch token: %error', ['%error' => $e->getMessage()]);
      return FALSE;
    }
  }

}
