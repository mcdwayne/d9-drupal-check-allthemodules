<?php

namespace Drupal\client_connection\Plugin\ClientConnection;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

/**
 * Provides a trait for plugins that use the guzzle client.
 *
 * @see \GuzzleHttp\ClientInterface
 */
trait HttpClientTrait {

  /**
   * A guzzle http client instance.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The initial client configuration.
   *
   * @return mixed
   *   An array of default configuration.
   */
  protected function getConfigDefaults() {
    return [
      'timeout' => NULL,
      'verify' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($option = NULL) {
    return $this->getClient()->getConfig($option);
  }

  /**
   * Returns the default http client.
   *
   * @return \GuzzleHttp\Client
   *   A guzzle http client instance.
   */
  protected function getClient() {
    if (is_null($this->httpClient)) {
      $this->httpClient = \Drupal::service('http_client_factory')->fromOptions($this->getConfigDefaults());
    }
    return $this->httpClient;
  }

  /**
   * Decode a json response.
   *
   * @param string $contents
   *   The string to decode.
   *
   * @return mixed|null
   *   The returned json, if decoded.
   */
  protected static function jsonDecode($contents) {
    if (empty($contents)) {
      return NULL;
    }
    return json_decode($contents, TRUE);
  }

  /**
   * Alter the request options passed to the request.
   *
   * @param string $method
   *   The request method being called.
   * @param string $uri
   *   The uri to make the request on.
   * @param array &$options
   *   The options to pass to the request.
   */
  protected function alterRequestOptions($method, $uri, array &$options) {
  }

  /**
   * {@inheritdoc}
   */
  public function request($method, $uri, array $options = []) {
    $this->alterRequestOptions($method, $uri, $options);
    return $this->getClient()->request($method, $uri, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function requestAsync($method, $uri, array $options = []) {
    $options[RequestOptions::SYNCHRONOUS] = FALSE;
    $this->alterRequestOptions($method, $uri, $options);
    return $this->getClient()->requestAsync($method, $uri, $options);
  }

  /**
   * Alter the send options passed to the client send method.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request being called.
   * @param array $options
   *   The options to pass to the request.
   */
  protected function alterSendOptions(RequestInterface $request, array &$options) {
  }

  /**
   * {@inheritdoc}
   */
  public function send(RequestInterface $request, array $options = []) {
    $this->alterSendOptions($request, $options);
    return $this->getClient()->send($request, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function sendAsync(RequestInterface $request, array $options = []) {
    $options[RequestOptions::SYNCHRONOUS] = FALSE;
    $this->alterSendOptions($request, $options);
    return $this->getClient()->sendAsync($request, $options);
  }

}
