<?php

namespace Drupal\spectra_connect;

use Drupal\spectra_connect\Entity\SpectraConnect;
use GuzzleHttp\Exception\ClientException;

/**
 * Class SpectraConnectUtilities.
 *
 * @package Drupal\spectra_connect
 *
 * Utility class
 */
class SpectraConnectUtilities {

  /**
   * SpectraConnectUtilities constructor.
   */
  public function __construct() {

  }

  /**
   * Queue a DELETE request to be executed later.
   *
   * @param string $connector
   *   The machine name of the connector.
   * @param array $data
   *   Array of Spectra-compatible DELETE data.
   */
  public static function spectraQueueDelete($connector, array $data) {
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('spectra_connect_queue_delete');
    $item = new \stdClass();
    $item->connector = $connector;
    $item->data = $data;
    $queue->createItem($item);
  }

  /**
   * Make a DELETE request to a Spectra server.
   *
   * @param string $connector
   *   The machine name of the connector.
   * @param array $data
   *   Array of Spectra-compatible DELETE data.
   *
   * @return bool|\Psr\Http\Message\ResponseInterface
   *   HTTP response from the DELETE request, or FALSE if the request fails.
   */
  public static function spectraDelete($connector, array $data) {
    $conn = SpectraConnect::load($connector);
    if ($conn) {
      $plugin = $conn->plugin;
      if ($plugin) {
        $data['plugin'] = $plugin;
      }
      $endpoint = $conn->delete_endpoint;
      $api_key = $conn->api_key;
      $headers = [];
      $headers['api-key'] = $api_key;
      $headers['Accept'] = 'application/json';
      $headers['Content-type'] = 'application/json';
      $options = ['headers' => $headers, 'body' => json_encode($data)];
      try {
        $response = \Drupal::httpClient()->delete($endpoint, $options);
        return $response;
      }
      catch (ClientException $e) {
        watchdog_exception('spectra_connect', $e);
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Make a GET request to a Spectra server.
   *
   * @param string $connector
   *   The machine name of the connector.
   * @param array $data
   *   Array of Spectra-compatible GET data.
   *
   * @return bool|\Psr\Http\Message\ResponseInterface
   *   HTTP response from the GET request, or FALSE if the request fails.
   */
  public static function spectraGet($connector, array $data) {
    $conn = SpectraConnect::load($connector);
    if ($conn) {
      $plugin = $conn->plugin;
      if ($plugin) {
        $data['plugin'] = $plugin;
      }
      $endpoint = $conn->get_endpoint;
      $api_key = $conn->api_key;
      $headers = [];
      $headers['api-key'] = $api_key;
      $headers['Accept'] = 'application/json';
      $headers['Content-type'] = 'application/json';

      // Build query string.
      // TODO add the Url class, and build the URL the right way.
      $query_string = http_build_query($data);
      $query_append = strpos($endpoint, '?') !== FALSE ? '&' : '?';
      $url = $endpoint . $query_append . $query_string;
      $options = ['headers' => $headers];
      try {
        $response = \Drupal::httpClient()->get($url, $options);
        return $response;
      }
      catch (ClientException $e) {
        watchdog_exception('spectra_connect', $e);
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Queue a POST request to be executed later.
   *
   * @param string $connector
   *   The machine name of the connector.
   * @param array $data
   *   Array of Spectra-compatible POST data.
   */
  public static function spectraQueuePost($connector, array $data) {
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('spectra_connect_queue_post');
    $item = new \stdClass();
    $item->connector = $connector;
    $item->data = $data;
    $queue->createItem($item);
  }

  /**
   * Make a POST request to a Spectra server.
   *
   * @param string $connector
   *   The machine name of the connector.
   * @param array $data
   *   Array of Spectra-compatible POST data.
   *
   * @return bool|\Psr\Http\Message\ResponseInterface
   *   HTTP response from the POST request, or FALSE if the request fails.
   */
  public static function spectraPost($connector, array $data) {
    $conn = SpectraConnect::load($connector);
    if ($conn) {
      $plugin = $conn->plugin;
      if ($plugin) {
        $data['plugin'] = $plugin;
      }
      $endpoint = $conn->post_endpoint;
      $api_key = $conn->api_key;
      $headers = [];
      $headers['api-key'] = $api_key;
      $headers['Accept'] = 'application/json';
      $headers['Content-type'] = 'application/json';
      $options = ['headers' => $headers, 'body' => json_encode($data)];
      try {
        $response = \Drupal::httpClient()->post($endpoint, $options);
        return $response;
      }
      catch (ClientException $e) {
        watchdog_exception('spectra_connect', $e);
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

}
