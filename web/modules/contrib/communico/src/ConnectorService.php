<?php
/**
 * @file
 * Contains \Drupal\communico\ConnectorService.
 */

namespace Drupal\communico;

use Drupal\Component\Serialization\Json;

/**
 * Provides connection for Communico.
 */
class ConnectorService {
 /**
  * Constructor.
  */
  public function __construct() {
  }

  /**
   * Retrieve authtoken based on provided info.
   */
  public function getAuthToken() {
    $auth_header = $this->getAuthHeaders();

    $request_headers = [];
    $request_headers[] = 'Content-Type: application/x-www-form-urlencoded;charset=UTF-8';
    $request_headers[] = 'Authorization: ' . $auth_header;

    $url = $this->getCommunicoUrl();
    $url = $url . '/v3/token';

    $data = $this->postToCommunico($url, $request_headers, 'grant_type=client_credentials');

    \Drupal::state()->set('communico.authHeader', $data['token_type'] . ' ' . $data['access_token']);

    $expire_time = time() + $data['expires_in'];

    $this->setTokenExpire($expire_time);
  }

  /**
   * Check if authtoken is valid or expired.
   *
   * @return boolean
   *   TRUE if valid, FALSE if not.
   */
  public function isAuthTokenValid() {
    $current_time = time();
    $token_expire = $this->getTokenExpire();

    if ($current_time >= $token_expire) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Retrieve feed from Communico.
   *
   * @param string $start_date
   *   Start date.
   * @param string $end_date
   *   End date.
   * @param string $type
   *   Type of items to retrieve.
   * @param string $limit
   *   Limit of number of items to retrieve.
   *
   * @return array
   *   An array of retrieved items.
   */
  public function getFeed($start_date, $end_date, $type, $limit) {
    if ($this->isAuthTokenValid() == FALSE) {
      $this->getAuthToken();
    }

    $token = \Drupal::state()->get('communico.authHeader');

    // If authtoken has expired, retrieve it again.
    // The second check here is to ensure it hasn't expired.
    if ($token == FALSE) {
      $this->getAuthToken();
      $token = \Drupal::state()->get('communico.authHeader');
    }

    $request_headers = [];
    $request_headers[] = 'Content-Type: application/json';
    $request_headers[] = 'Accept: application/json';
    $request_headers[] = 'Authorization: ' . $token;

    $params = [];
    $params['startDate'] = $start_date;
    $params['endDate'] = $end_date;
    $params['type'] = $type;
    $params['limit'] = $limit;

    $url = $this->getCommunicoUrl();
    $url = $url . '/v3/attend/events?status=published&start=0&';

    $data = $this->getFromCommunico($url, $params, $request_headers);

    $next_fetch = \Drupal::state()->get('communico.nextFetch');

    // If data is null or cached rely on cache data.
    if ($data == NULL || $data == FALSE || $next_fetch > time()) {
      $data = \Drupal::state()->get('communico.dataCache');
      $data = unserialize($data);

      return $data;
    }
    else {
      // We are fetching from Communico.
      // Fetch and set both the cache and next fetch timestamp.
      $serialized = serialize($data['data']['entries']);
      \Drupal::state()->set('communico.dataCache', $serialized);
      \Drupal::state()->set('communico.nextFetch', time() + (60*5));

      return $data['data']['entries'];
    }
  }

  /**
   * Post request to communico.
   *
   * @param string $url
   *   Communico url.
   * @param array $headers
   *   Headers to be sent.
   * @param string $body
   *   Body of request.
   *
   * @return object
   *   An object of the json response.
   */
  protected function postToCommunico($url, $headers, $body = NULL) {
    $result = NULL;
    $curl = curl_init();
    // Set curl opts, timeouts prevent communico from effecting site up time.
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

    $result = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    return Json::decode($result);
  }

  /**
   * Perform get request to Communico.
   *
   * @param string $url
   *   Communico url.
   * @param array $params
   *   Params to pass.
   * @param array $headers
   *   Headers: [description].
   *
   * @return object
   *   Object of json return.
   */
  protected function getFromCommunico($url, $params, $headers) {
    $result = NULL;
    $query = '';

    // Loop params and build url with params as a get variables appended.
    foreach ($params as $key => $value) {
      $query .= $key . '=' . $value . '&';
    }
    $url = $url . $query;

    $curl = curl_init();
    // Set curl opts timeouts re key to prevent communico from effecting site up time.
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($curl);

    curl_close($curl);

    return Json::decode($result);
  }

  /**
   * Set the token expire date.
   *
   * @param string $timestamp
   *   Valid timestamp.
   */
  protected function setTokenExpire($timestamp) {
    \Drupal::state()->set('communico.token_expire', $timestamp);
  }

  /**
   * Get the token expire date.
   *
   * @return string
   *   Timestamp.
   */
  protected function getTokenExpire() {

    return \Drupal::state()->get('communico.token_expire');
  }

  /**
   * Get Communico url.
   *
   * @return string
   *   Valid url.
   */
  protected function getCommunicoUrl() {
    $config = \Drupal::config('communico.settings');

    return $config->get('url');
  }

  /**
   * Retrieve an authheader.
   *
   * @return string
   *   Properly formated auth header.
   */
  protected function getAuthHeaders() {
    // This builds a basic auth header for communico based upon proper key:secret format.
    $config = \Drupal::config('communico.settings');
    $key = $config->get('access_key');
    $secret = $config->get('secret_key');
    $auth = $key . ':' . $secret;
    $auth = base64_encode($auth);

    return 'Basic ' . $auth;
  }
}
