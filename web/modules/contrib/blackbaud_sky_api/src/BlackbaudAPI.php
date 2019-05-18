<?php

namespace Drupal\blackbaud_sky_api;

use Drupal\blackbaud_sky_api\Blackbaud;

/**
 * Class BlackbaudAPI.
 *
 * @package Drupal\blackbaud_sky_api
 */
class BlackbaudAPI extends Blackbaud {

  /**
   * Get the Developer Key, the bb-api-subscription-key.
   *
   * @return string
   *   The Developer Key.
   */
  protected function getDevKey() {
    return $this->state->get('blackbaud_sky_api_dev_key');
  }

  /**
   * Get the API base URL we are checking.
   *
   * @return string
   *   The OAuth base URL.
   */
  protected function getApiBaseUrl() {
    return $this->state->get('blackbaud_sky_api_url', BlackbaudInterface::BLACKBAUD_SKY_API_URL);
  }

  /**
   * Sets the typical headers needed for an api call.
   *
   * @return array
   *   the headers for the api call.
   */
  protected function setApiHeaders() {
    // Grab the access token.
    $token = parent::getToken('access');

    // Return the set headers for an API call.
    return [
      'headers' => [
        'bb-api-subscription-key' => $this->getDevKey(),
        'Authorization' => 'Bearer ' . $token,
      ],
    ];
  }

  /**
   * Mechanism to call the API.
   *
   * @param string $endpoint
   *   The endpoint url without the base api url.
   * @param string $type
   *   The type of request (ie GET, POST, etc).
   *
   * @return null|object
   *   The API response or NULL.
   */
  public function callApi($endpoint, $type = 'GET') {
    parent::setUrl($this->getApiBaseUrl() . $endpoint);
    parent::requestResponse($type, $this->setApiHeaders());

    // Exit if Empty Response.
    if ($this->request === NULL) {
      return NULL;
    }

    // Grab the Body and return as needed.
    $contents = json_decode($this->request->getBody()->getContents());
    return !empty($contents) ? $contents : NULL;
  }

  /**
   * Pings a simple api call to test if the token is valid.
   *
   * @return bool
   *   If we are able to ping the API or not.
   */
  public function checkToken() {
    $check = $this->callApi('/constituent/v1/addresstypes');
    return $check === NULL ? FALSE : TRUE;
  }

}
