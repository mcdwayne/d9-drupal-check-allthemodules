<?php

namespace Drupal\aegir_site_subscriptions\HostingServiceCalls;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use GuzzleHttp\Client;

/**
 * Base class for Web service calls to the hosting service.
 */
abstract class HostingServiceCall {

  /**
   * The response data received after sending a request.
   *
   * @var array
   */
  protected $response;

  /**
   * The response headers received after sending a request.
   *
   * @var array
   */
  protected $headers;

  /**
   * The response code received after sending a request.
   *
   * @var int
   */
  protected $status;

  /**
   * The logging service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A Guzzle http client instance.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Class Constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logging service.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP/S client.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   This module's configuration.
   */
  public function __construct(
      LoggerChannelFactory $logger_factory,
      Client $http_client,
      ConfigFactory $config_factory) {
    $this->logger = $logger_factory->get('aegir_site_subscriptions');
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('aegir_site_subscriptions.settings');
  }

  /**
   * Executes the hosting service call.
   *
   * Implementing classes must do the following:
   *   1. Make a service call to Aegir that does or returns something.
   *   2. If task creation is required, set $this->$task_id to the task ID
   *      returned in the call.
   *   3. Return $this for method chaining.
   *
   * @return $this
   *   The object itself.
   */
  abstract protected function execute();

  /**
   * Returns the endpoint for the Aegir Web service.
   *
   * @return string
   *   The endpoint URL.
   */
  protected function getAegirServiceEndpoint() {
    $endpoint = $this->config->get('service_endpoint_url');
    if (empty($endpoint)) {
      $this->logger->critical('The Aegir service endpoint has not been configured.');
    }
    return $endpoint;
  }

  /**
   * Sends a request to the Aegir host, and sets class variables with results.
   *
   * @param string $resource
   *   The endpoint resource to query.
   * @param array $data
   *   The data to send.
   */
  protected function sendRequestAndReceiveResponse($resource, array $data) {
    $target = $this->getAegirServiceEndpoint() . '/' . $resource;

    $this->logRequest($resource, $data);

    try {
      $request = empty($data) ? $this->sendGetRequest($target) : $this->sendPostRequest($target, $data);
    }
    catch (\Exception $e) {
      watchdog_exception('aegir_site_subscriptions', $e);
    }

    $this->status = $request->getStatusCode();
    $this->headers = $request->getHeaders();
    $this->response = json_decode((string) $request->getBody(), TRUE);
  }

  /**
   * Log the request before sending it.
   *
   * @param $resource
   *   The resource endpoint.
   * @param $data
   *   The data being sent.
   */
  protected function logRequest($resource, $data) {
    $this->logger
      ->info('Sending remote request for resource "%resource" with headers "%headers", arguments "%arguments" and data "%data"', [
        '%resource' => $resource,
        '%headers' => serialize($this->getHeadersToSendWithoutApiKey()),
        '%arguments' => serialize($this->getQueryParametersToSend()),
        '%data' => serialize($data),
      ]);
  }

  /**
   * Send a GET request.
   *
   * @param $target
   *   Where the request should be sent.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response returned from the request.
   */
  protected function sendGetRequest($target) {
    return $this->httpClient->get($target, [
      'headers' => $this->getHeadersToSendWithApiKey(),
      'query' => $this->getQueryParametersToSend(),
    ]);
  }

  /**
   * Send a POST request.
   *
   * @param $target
   *   Where the request should be sent.
   * @param $data
   *   The data to send to the endpoint.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response returned from the request.
   */
  protected function sendPostRequest($target, $data) {
    return $this->httpClient->post($target, [
      'headers' => $this->getHeadersToSendWithApiKey(),
      'json' => $data,
    ]);
  }

  /**
   * Fetches the headers to send on a request without the API key.
   */
  protected function getHeadersToSendWithoutApiKey() {
    return [];
  }

  /**
   * Fetches the headers to send on a request including the API key.
   */
  protected function getHeadersToSendWithApiKey() {
    return $this->getHeadersToSendWithoutApiKey() + [
      'API-KEY' => $this->config->get('service_endpoint_key'),
    ];
  }

  /**
   * Fetches the query parameters to send in the request.
   */
  protected function getQueryParametersToSend() {
    return [];
  }

  /**
   * Fetches the request response.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Get the action performed by this hosting service call.
   */
  public static function getActionPerformed() {
    return static::ACTION_PERFORMED;
  }

  /**
   * Record a message about the successful action in the log.
   *
   * @return $this
   *   The object itself.
   */
  abstract protected function recordSuccessLogMessage();

  /**
   * Fetches the name of the current class.
   *
   * @return string
   *
   * @throws \ReflectionException
   */
  protected function getClassName() {
    return (new \ReflectionClass($this))->getShortName();
  }

  /**
   * Perform an action using the remote Aegir service and log the results.
   *
   * As we may be in the middle of a save operation, or want to run several
   * tasks without saving the log every time, we should not save the site after
   * we update its task log here. This must be done by the caller.
   *
   * @return $this
   *   The object itself.
   */
  public function performActionAndLogResults() {
    try {
      $this->execute()->recordSuccessLogMessage();
    }
    catch (\Exception $e) {
      watchdog_exception('aegir_site_subscriptions', $e);
    }
    return $this;
  }

}
