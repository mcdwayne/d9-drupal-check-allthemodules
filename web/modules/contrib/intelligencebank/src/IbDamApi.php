<?php

namespace Drupal\ib_dam;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ib_dam\Exceptions\AssetDownloaderBadRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * IbDamApi service to fetch remote files.
 *
 * @package Drupal\ib_dam
 */
final class IbDamApi {

  private $authKey;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The logger channel for IntelligenceBank DAM.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   Http client service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger chanel for IntelligenceBank DAM.
   */
  public function __construct(Client $http_client, LoggerChannelInterface $loggerChannel) {
    $this->httpClient = $http_client;
    $this->logger     = $loggerChannel;
  }

  /**
   * Set auth key property to use in any request to the remote IB api.
   */
  public function setAuthKey($key) {
    $this->authKey = $key;
    return $this;
  }

  /**
   * Creates request and run it.
   *
   * @param string $url
   *   The url of the resource.
   *
   * @return null|\Psr\Http\Message\ResponseInterface
   *   Response object.
   */
  public function fetchResource($url) {
    if (!$this->isValidRequestParams($url)) {
      return NULL;
    }

    try {
      // @todo: try to retry a couple of times, see https://goo.gl/qcz3DG.
      $response = $this->httpClient->get($url, $this->getHeaders());
    }
    catch (RequestException $e) {
      (new AssetDownloaderBadRequest($e->getMessage()))
        ->logException();
      return NULL;
    }

    return $response;
  }

  /**
   * Set default header for request validation on remote side.
   */
  private function getHeaders() {
    return ['headers' => ['Cookie' => '_aid=' . $this->authKey]];
  }

  /**
   * Check if request url is valid and auth key is ready to use.
   */
  private function isValidRequestParams($url) {
    if (!$this->authKey || !$url) {
      $params  = $this->getLogParams($url);
      $message = 'Missing required params.';

      (new AssetDownloaderBadRequest($message, $params))
        ->logException();
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Helper function to log error messages.
   */
  private function getLogParams($url = NULL) {
    return print_r([
      'url' => $url ?: 'No url',
      'auth' => $this->authKey ? 'xxxxxxxxxx' : 'No auth',
    ], TRUE);
  }

}
