<?php

/**
 * @file
 * Handles API calls to the Fastly service.
 */

namespace Drupal\edgecast;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Drupal\edgecast\Exceptions\FailedRequest;

/**
 * EdgeCast API for Drupal.
 * It's responsible to integrate with EdgeCast to clear the cache.
 */
class EdgeCastApi {

  /**
   * The EdgeCast logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The EdgeCast customer id.
   *
   * @var integer
   */
  protected $customerId;

  /**
   * The EdgeCast token.
   *
   * @var string
   */
  protected $token;

  /**
   * The EdgeCast path.
   *
   * @var string.
   */
  protected $path;

  /**
   * Guzzle http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Response HTTP valid codes.
   */
  const ResponseValidCodes = [
    200,
    302,
    304
  ];

  /**
   * Constructs a \Drupal\edgecast\EdgeCastApi object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The Edgecast logger channel.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, LoggerInterface $logger) {
    $config = $config_factory->get('edgecast.api');

    $this->customerId = $config->get('edgecast_customer');
    $this->token = $config->get('edgecast_token');
    $this->path = $config->get('edgecast_path');

    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * Purge url.
   *
   * @param string $path
   *   Path to clear.
   *
   * @return bool TRUE if request properly cleared the cache false otherwise.
   *   TRUE if request properly cleared the cache false otherwise.
   *
   * @throws \Drupal\edgecast\Exceptions\FailedRequest
   */
  public function purgePath($path) {
    // Test if configuration was properly set.
    if (empty($this->token) || empty($this->path) || empty($this->customerId)) {
      return FALSE;
    }

    $headers = $this->getHeaders();
    $body = $this->getBody($path);

    try {
      $response = $this->httpClient->request('PUT', $this->getEndpoint(), ['headers' => $headers, 'body' => json_encode($body)]);
      if (!in_array($response->getStatusCode(), EdgeCastApi::ResponseValidCodes)) {
        return FALSE;
      }
      
      return TRUE;
    }
    catch (RequestException $e) {
      $response = $e->getResponse();
      // Generate exception if error 400 otherwise reuturn false.
      if ($response->getStatusCode() == 400) {
        throw new FailedRequest($e->getMessage());
      }
    }

    return FALSE;
  }

  /**
   * Return the endpoint url with correctly customerId.
   *
   * @return string
   *  The endpoint url.
   */
  private function getEndpoint() {
    return 'https://api.edgecast.com/v2/mcc/customers/' . $this->customerId . '/edge/purge';
  }

  /**
   * Return headers to the request object.
   *
   * @return array
   *   Necessary headers.
   */
  private function getHeaders() {
    return [
      'Authorization' => 'TOK:' . $this->token,
      'Accept' => 'application/json',
      'Content-Type' => 'application/json',
    ];
  }

  /**
   * Return Request body.
   *
   * @param $path
   *   Internal url to clear.
   *
   * @return array
   *   Return object with correctly path to clear.
   */
  private function getBody($path) {
    return [
      'MediaPath' => $this->path . $path,
      'MediaType' => 8,
    ];
  }
}
