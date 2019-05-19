<?php
/**
 * @file
 * Base class for the Zoom API.
 */
namespace Drupal\zoom_conference\Api;

use Drupal\Core\Site\Settings;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Serialization\Json;

// JWT PHP Library https://github.com/firebase/php-jwt
use \Firebase\JWT\JWT;

/**
 * Zoom API Base Class.
 */
class ZoomAPI {

  /**
   * API Key.
   *
   * @var string
   */
  private $apiKey;

  /**
   * API Secret.
   *
   * @var string
   */
  private $apiSecret;

  /**
   * API URL.
   *
   * @var string
   */
  private $apiUrl;

  /**
   * Webhooks Enabled.
   *
   * @var integer
   */
  private $apiWebhooksEnabled;

  /**
   * Webhooks Username.
   *
   * @var string
   */
  private $apiWebhooksUsername;

  /**
   * Webhooks Password.
   *
   * @var string
   */
  private $apiWebhooksPassword;

  /**
   * Debug Mode Enabled.
   *
   * @var integer
   */
  private $apiDebug;

  /**
   * Class constructor.
   */
  public function __construct() {
    // Get settings.
    $config = \Drupal::config('zoom_conference.settings');
    $this->apiKey = $config->get('zoom_conference_key');
    $this->apiSecret = $config->get('zoom_conference_secret');
    $this->apiUrl = $config->get('zoom_conference_url');
    $this->apiWebhooksEnabled = $config->get('zoom_conference_webhooks_enabled');
    $this->apiWebhooksUsername = $config->get('zoom_conference_webhooks_username');
    $this->apiWebhooksPassword = $config->get('zoom_conference_webhooks_password');
    $this->apiDebug = $config->get('zoom_conference_debug');
  }

  /**
   * Sign JWT token.
   */
  protected function generateJWT() {
    $token = [
      "iss" => $this->apiKey,
      "exp" => time() + 60
    ];
    return JWT::encode($token, $this->apiSecret);
  }

  /**
   * Send Request.
   */
  protected function sendRequest($endpoint, $method = 'GET', $data = array()) {
    // Debug.
    if ($this->apiDebug == 1) {
      \Drupal::logger('zoom_conference')
        ->debug('Sending: @endpoint => @data', [
          '@endpoint' => $endpoint,
          '@data' => '<pre>' . print_r($data, TRUE) . '</pre>',
        ]);
    }

    // Set up request url.
    $request_url = $this->apiUrl . $endpoint;
    if (substr($request_url, 0, 8) !== "https://") {
      $request_url = 'https://' . $request_url;
    }

    // Set up POST fields. Used for all methods except GET.
    $post_fields = json_encode($data);

    try {
      // Used for all methods.
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

      // Method specific code.
      if ($method == 'GET') {
        if (!empty($data)) {
          $request_url .= '?' . UrlHelper::buildQuery($data);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Authorization: Bearer ' . $this->generateJWT(),
        ]);
      }
      else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Authorization: Bearer ' . $this->generateJWT(),
          'Content-Type: application/json',
          'Content-Length: ' . strlen($post_fields),
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      }

      // Set the url.
      curl_setopt($ch, CURLOPT_URL, $request_url);

      // Get the response.
      $response = curl_exec($ch);
      curl_close($ch);
      $response = Json::decode($response);
    }
    catch (Exception $e) {
      // Log error.
      \Drupal::logger('zoom_conference')
        ->error('An error occurred making a call to @endpoint with data @data. Exception: @exception.', [
          '@endpoint' => $endpoint,
          '@data' => $post_fields,
          '@exception' => $e->getMessage(),
        ]);

      // Return.
      return [];
    }

    if (isset($response['error'])) {
      // Log error.
      \Drupal::logger('zoom_conference')
        ->error('An error occurred making a call to @endpoint with data @data. Error code: @code. Message: @message', [
          '@endpoint' => $endpoint,
          '@data' => $post_fields,
          '@code' => $response['error']['code'],
          '@message' => $response['error']['message'],
        ]);

      // Return.
      return [];
    }

    return $response;
  }

}
