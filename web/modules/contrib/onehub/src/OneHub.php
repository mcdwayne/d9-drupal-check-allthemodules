<?php

namespace Drupal\onehub;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class OneHub.
 *
 * @package Drupal\onehub
 */
abstract class OneHub {

  /**
   * The Base URL for the API.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * The Redirect URI.
   *
   * @var string
   */
  protected $redirectUri;

  /**
   * The url we are checking.
   *
   * @var string
   */
  protected $url;

  /**
   * The Request Response.
   *
   * @var GuzzleHttp\Client
   */
  protected $request;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->config = \Drupal::config('onehub.settings');
    $this->baseUrl = ONEHUB_BASE_URL;

    // Sets up the redirect uri for later use.
    global $base_url;
    $base = $base_url;

    // Make sure we are on https.
    if (strpos($base, 'http:') !== FALSE) {
      $base = str_replace('http:', 'https:', $base);
    }
    $this->redirectUri = $base . ONEHUB_REDIRECT_URI;
  }

  /**
   * Set the url we are checking.
   *
   * @param string $url
   *   The url string we are checking.
   */
  protected function setUrl($url) {
    $this->url = $url;
  }

  /**
   * Get the url we are checking.
   *
   * @return string
   *   The url string we are checking.
   */
  protected function getUrl() {
    return $this->url;
  }

  /**
   * Set the url we are checking.
   *
   * @param string $type
   *   The type of token we are setting.
   * @param string $token
   *   The token we are setting.
   */
  protected function setToken($type, $token) {
    \Drupal::configFactory()->getEditable('onehub.settings')->set('onehub_' . $type . '_token', $token)->save();
  }

  /**
   * Get the url we are checking.
   *
   * @return string
   *   The type of token we are getting.
   */
  protected function getToken($type) {
    $token = \Drupal::config('onehub.settings')->get('onehub_' . $type . '_token');
    return $token;
  }

  /**
   * Get the Access code.
   *
   * @param string $code
   *   The $_GET param from the Oauth Callback page.
   */
  protected function getAccessCode($code) {
    // Access Token Url.
    $this->setUrl($this->baseUrl . '/oauth/token');

    // Grab these for the auth header.
    $client_id = $this->config->get('onehub_application_id');
    $client_secret = $this->config->get('onehub_client_secret');

    // Set the options.
    $options = [
      'form_params' => [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $this->redirectUri,
      ],
    ];

    // Post the Data.
    $this->requestResponse('POST', $options);

    // Decode and grab the response.
    $contents = json_decode($this->request->getBody()->getContents());

    // Set the access token.
    $this->setToken('access', $contents->access_token);

    $response = new RedirectResponse('/admin/config/services/onehub/authorize');
    $response->send();
  }

  /**
   * Gets the response of a page.
   *
   * @param string $type
   *   The type of request (ie GET, POST, etc).
   * @param array $options
   *   Any options to pass in.
   */
  protected function requestResponse($type, array $options = []) {
    $client = new Client();

    // Set the options for the request.
    // @see http://docs.guzzlephp.org/en/latest/request-options.html
    $options += [
      'http_errors' => FALSE,
      'timeout' => 3,
      'connect_timeout' => 3,
      'synchronous' => TRUE,
    ];

    try {
      // Try the request.
      $response = $client->request($type, $this->getUrl(), $options);

      // Check the Status code and return.
      switch ($response->getStatusCode()) {
        // All good, send back response.
        case '200':
        case '201':
          $this->request = $response;
          break;

        // Need to reauth.
        case '401':
          $this->getAuthCode('refresh', $this->getToken('refresh'));
          break;

        // Something else is amiss.
        default:
          $message = 'The request to the OneHub API resulted in a ' . $response->getStatusCode() . ' Response. ';
          \Drupal::logger('OneHub API')->error($message);
          $this->request = NULL;
          break;
      }
    }
    catch (TransferException $e) {
      $this->request = NULL;
    }
  }

}
