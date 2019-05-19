<?php

namespace Drupal\widencollective;

/**
 * Class WidencollectiveSearchService.
 *
 * @package Drupal\widencollective
 */
class WidencollectiveSearchService implements WidencollectiveSearchServiceInterface {

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
   * Executes a request to widen api to fetch search UI url.
   *
   * @return array
   *   Returns an array with HTTP status and URL.
   */
  public static function getSearchConnectorUiUrl($access_token) {
    if (empty($access_token)) {
      $message = [
        'http_status' => '403',
        'error' => t('No token was provided, please enable your access to Widen Collective first under account.'),
      ];

      return $message;
    }

    $config = self::getConfig();
    $search_endpoint = $config->get('search_endpoint');

    // Initiate and process the response of the HTTP request.
    $response = \Drupal::httpClient()
      ->get($search_endpoint, [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);

    $http_status = $response->getStatusCode();

    // Return an error array.
    if ($http_status != '200') {
      $err = [
        'status_code' => $http_status,
        'message' => t('Error Response : @status.', ['@status' => $http_status]),
      ];

      return $err;
    }

    $search_url = json_decode($response->getBody());
    $result = [
      'status_code' => $http_status,
      'url' => $search_url->url,
    ];

    return $result;
  }

}
