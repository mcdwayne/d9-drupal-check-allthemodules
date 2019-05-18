<?php

namespace Drupal\blackbaud_sky_api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\blackbaud_sky_api\BlackbaudInterface;

/**
 * Class Blackbaud.
 *
 * @package Drupal\blackbaud_sky_api
 */
abstract class Blackbaud implements BlackbaudInterface {

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
   * @var \GuzzleHttp\Client
   */
  protected $request;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   */
  public function __construct() {
    // @todo one day I need to redo this all to properly inject these classes
    // It would probably affect any submodule people created off this as well.
    // So for now this "works".
    $this->state = \Drupal::service('state');
    $this->requestStack = \Drupal::service('request_stack');

    // Grab the base path.
    $base = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();

    // Make sure we are on https.
    if (strpos($base, 'http:') !== FALSE) {
      $base = str_replace('http:', 'https:', $base);
    }

    // Set the full uri.
    $api_url = $this->state->get('blackbaud_sky_api_redirect_uri', BlackbaudInterface::BLACKBAUD_SKY_API_REDIRECT_URI);
    $this->redirectUri = $base . '/' . $api_url;
  }

  /**
   * Get the Oauth base URL we are checking.
   *
   * @return string
   *   The OAuth base URL.
   */
  protected function getOauthBaseUrl() {
    return $this->state->get('blackbaud_sky_api_oauth_url', BlackbaudInterface::BLACKBAUD_SKY_API_OAUTH_URL);
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
    $this->state->set('blackbaud_sky_api_' . $type . '_token', $token);
  }

  /**
   * Get the url we are checking.
   *
   * @return string
   *   The type of token we are getting.
   */
  protected function getToken($type) {
    return $this->state->get('blackbaud_sky_api_' . $type . '_token');
  }

  /**
   * Get the Authorization code.
   *
   * @param string $type
   *   The type of Auth Code Request (init or refresh).
   * @param string $code
   *   The $_GET param from the Oauth Callback page.
   */
  protected function getAuthCode($type, $code) {
    // Access Token Url.
    $this->setUrl($this->getOauthBaseUrl() . '/token');

    // Grab these for the auth header.
    $client_id = $this->state->get('blackbaud_sky_api_application_id');
    $client_secret = $this->state->get('blackbaud_sky_api_application_secret');

    // Set header for this post request.
    $header = [
      'Content-type' => 'application/x-www-form-urlencoded',
      'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
    ];

    // Set body for the type of post request.
    switch ($type) {
      // Initial Request.
      case 'init':
        $body = [
          'grant_type' => 'authorization_code',
          'code' => $code,
          'redirect_uri' => $this->redirectUri,
        ];
        break;

      // Refresh Request.
      case 'refresh':
        $body = [
          'grant_type' => 'refresh_token',
          'refresh_token' => $code,
        ];
        break;
    }

    // Set the options.
    $options = [
      'headers' => $header,
      'body' => http_build_query($body),
    ];

    // Post the Data.
    $this->requestResponse('POST', $options);

    // Decode and grab the response.
    $contents = json_decode($this->request->getBody()->getContents());

    // Set the refresh token for later use.
    $this->setToken('refresh', $contents->refresh_token);

    // Set the access token.
    $this->setToken('access', $contents->access_token);

    // Go back to authorization page.
    if ($type === 'init') {
      $response = new RedirectResponse('/admin/config/services/blackbaud/authorize');
      $response->send();
    }

    // Refresh Request return.
    return $type === 'refresh' ? TRUE : NULL;
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
          $this->request = $response;
          // Reset the quota variable.
          $this->state->set('blackbaud_sky_api_quota_reached', FALSE);
          break;

        // Need to reauth.
        case '401':
          $this->getAuthCode('refresh', $this->getToken('refresh'));
          break;

        // Quota reached.
        case '403':
          // Grab the message with the time.
          $message = json_decode($response->getBody()->getContents());
          $time = strstr($message->message, 'in ');
          $time = str_replace('in ', '', $time);

          // Convert this to seconds.
          $seconds = strtotime("1970-01-01 $time UTC");

          // Add this to NOW and what's left.
          $future = REQUEST_TIME + $seconds;

          // Set the readable date.
          $date = date('M j, Y H:i:s', $future);

          // Let the user know what's up.
          $message = 'You have reached your quota limit.  Your quota resets at ' . $date;
          \Drupal::logger('Blackbaud SKY API')->error($message);

          // Set variable and leave.
          $this->state->set('blackbaud_sky_api_quota_reached', TRUE);
          $this->request = NULL;
          break;

        // Something else is amiss.
        default:
          $message = 'The request to the Blackbaud API resulted in a ' . $response->getStatusCode() . ' Response.';
          \Drupal::logger('Blackbaud SKY API')->error($message);
          $this->request = NULL;
          break;
      }
    }
    catch (TransferException $e) {
      $this->request = NULL;
    }
  }

}
