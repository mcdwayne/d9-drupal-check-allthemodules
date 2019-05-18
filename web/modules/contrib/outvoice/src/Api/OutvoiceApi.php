<?php

namespace Drupal\outvoice\Api;

/**
 * Class for connecting to the OutVoice API.
 */
class OutvoiceApi {

  /**
   * The OutVoice API address.
   *
   * @var string
   */
  protected $apiUrl = 'https://api.outvoice.com/';

  /**
   * The OutVoice API Version.
   *
   * @var string
   */
  protected $apiVersion = 'api/v1.0/';

  /**
   * The OutVoice module version.
   *
   * @var string
   */
  protected $moduleVersion = 'drupal8.x-1.1';

  /**
   * The OutVoice Client ID.
   *
   * @var string
   */
  protected $clientID = '08a3b847-025c-40a9-bfc8-4ed891286f99';

  /**
   * OAuth Access Token.
   *
   * @var string
   */
  public $accessToken;

  /**
   * OAuth Refresh Token.
   *
   * @var string
   */
  public $refreshToken;

  /**
   * OAuth token expires.
   *
   * @var string
   */
  public $tokenExpires;

  /**
   * Generates OAuth tokens from username and password.
   *
   * @param string $username
   *   User's OutVoice username.
   * @param string $password
   *   User's OutVoice password.
   *
   * @return bool
   *   Returns TRUE if successful.
   */
  public function generateTokens($username, $password) {

    $data = [
      'form_params' => [
        'grant_type' => 'password',
        'client_id' => $this->clientID,
      ],
    ];
    $data['form_params']['username'] = $username;
    $data['form_params']['password'] = $password;
    $client = \Drupal::httpClient();
    $request = $client->post($this->apiUrl . "oauth/token", $data);
    $tokens = json_decode($request->getBody());
    if (isset($tokens->access_token)) {
      $this->accessToken = $tokens->access_token;
      $this->refreshToken = $tokens->refresh_token;
      $this->tokenExpires = time() + 3000;
      return TRUE;
    }
    return FALSE;

  }

  /**
   * Refreshes OAuth tokens.
   *
   * @return bool
   *   Returns TRUE if successful.
   */
  public function refreshTokens() {

    $data = [
      'form_params' => [
        'grant_type' => 'refresh_token',
        'client_id' => $this->clientID,
      ],
    ];
    $data['form_params']['refresh_token'] = $this->refreshToken;
    $client = \Drupal::httpClient();
    $request = $client->post($this->apiUrl . "oauth/token", $data);
    $tokens = json_decode($request->getBody());
    if (isset($tokens->access_token)) {
      $this->accessToken = $tokens->access_token;
      $this->refreshToken = $tokens->refresh_token;
      $this->tokenExpires = time() + 3000;
      return TRUE;
    }
    return FALSE;

  }

  /**
   * Sets OAuth tokens.
   *
   * @param string $access
   *   OAuth access token.
   * @param string $refresh
   *   OAuth refresh token.
   */
  public function setTokens($access, $refresh) {
    $this->accessToken = $access;
    $this->refreshToken = $refresh;
  }

  /**
   * Retrieves access token and refreshes it if needed.
   */
  public function getAccessToken() {
    if (time() > $this->tokenExpires) {
      $this->refreshTokens();
    }
    return $this->accessToken;
  }

  /**
   * Retrieves refresh token.
   */
  public function getRefreshToken() {
    return $this->refreshToken;
  }

  /**
   * Handles an OutVoice payment.
   *
   * @param array $info
   *   An array of values for the payment.
   *     - freelancer: The ID of the contributor.
   *     - amount: The payment amount.
   *     - currency: Payment currency.
   *     - freelancer1: ID of second contributor (optional).
   *     - amount1: The second payment amount (optional).
   *     - url: The URL of the content published.
   *     - title: The title of the content published.
   *
   * @return string
   *   Returns message in plain text.
   */
  public function payment(array $info) {

    $data = [
      "headers" => [
        'Authorization' => "Bearer " . $this->getAccessToken(),
        'Content-Type'  => "application/json",
      ],
      "body"    => [
        0 => [
          "freelancer" => $info['freelancer'],
          "amount"     => self::outvoiceFormatAmount($info['amount']),
          "currency"   => $info['currency'],
          "url"        => $info['url'],
          "title"      => $info['title'],
          "version"    => $this->moduleVersion,
        ],
      ],
    ];
    // Add second contributor.
    if (!empty($info['amount1'])) {
      $data['body'][1] = [
        "freelancer" => $info['freelancer1'],
        "amount"     => self::outvoiceFormatAmount($info['amount1']),
        "currency"   => $info['currency1'],
        "url"        => $info['url'],
        "title"      => $info['title'],
        "version"    => $this->moduleVersion,
      ];
    }
    $data['body'] = json_encode($data['body']);
    $client = \Drupal::httpClient();
    $request = $client->post($this->apiUrl . $this->apiVersion . "transaction", $data);
    $message = "There was an error. Please log into your OutVoice account to confirm payment.";
    if ($request->getStatusCode() == 200) {
      $message = "OutVoice payment successful. " . $request->getBody();
    }

    return $message;

  }

  /**
   * List of all contributors.
   *
   * @return array
   *   Returns an array of contributors keyed by their IDs
   */
  public function listContributors() {

    $data = [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->getAccessToken(),
      ],
    ];
    $client = \Drupal::httpClient();
    $request = $client->get($this->apiUrl . $this->apiVersion . "list-freelancers", $data);
    $contributorList = json_decode($request->getBody());
    return $contributorList;

  }

  /**
   * Formats currency amounts.
   *
   * @param int $amount
   *   Number indicating amount to be paid.
   *
   * @return string
   *   Formatted without decimal.
   */
  public static function outvoiceFormatAmount($amount) {

    $decimal = strpos($amount, ".");
    if ($decimal === FALSE) {
      $amount = $amount . "00";
    }
    else {
      // Ensure 2 digits after decimal.
      $exploded = explode(".", $amount);
      if (empty(strlen($exploded[1]))) {
        $amount = $amount . "00";
      }
      if (strlen($exploded[1]) == 1) {
        $amount = $amount . "0";
      }
    }
    return $amount;

  }

}
