<?php

namespace Drupal\commerce_partpay\PartPay;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

/**
 * Class Payment Express Service.
 *
 * @package Drupal\commerce_partpay
 */
class AbstractAbstractPartPayRequest implements AbstractPartPayInterface {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  public $logger;

  /**
   * Commerce gateway configuration.
   *
   * @var array
   */
  public $configuration;

  /**
   * Provides HTTP client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Gateway test mode.
   *
   * @var bool
   */
  protected $testMode;

  /**
   * Set Token Request Mode.
   *
   * @var bool
   */
  protected $tokenRequestMode = FALSE;

  /**
   * Sandbox Endpoint URL.
   *
   * @var string
   */
  protected $testEndpoint = 'https://api-ci.partpay.co.nz';

  /**
   * Live Endpoint URL.
   *
   * @var string
   */
  protected $liveEndpoint = 'https://api.partpay.co.nz';

  /**
   * Sandbox Endpoint URL.
   *
   * @var string
   */
  protected $testTokenEndpoint = 'https://partpay-dev.au.auth0.com';

  /**
   * Live Endpoint URL.
   *
   * @var string
   */
  protected $liveTokenEndpoint = 'https://partpay.au.auth0.com';

  /**
   * Test Audience URL.
   *
   * @var string
   */
  protected $testAudience = 'https://auth-dev.partpay.co.nz';

  /**
   * Live Audience URL.
   *
   * @var string
   */
  protected $liveAudience = 'https://auth.partpay.co.nz';

  /**
   * Auth ClientId.
   *
   * @var string
   */
  protected $clientId;

  /**
   * Auth Secret.
   *
   * @var string
   */
  protected $secret;

  /**
   * Token.
   *
   * @var string
   */
  protected $token;

  /**
   * Token Expiry.
   *
   * @var string
   */
  protected $tokenExpiry;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Guzzle http client.
   */
  public function __construct(LoggerInterface $logger, ClientInterface $httpClient) {
    $this->logger = $logger;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function setTestMode() {
    return $this->testMode = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isTestMode() {
    return $this->getSettings('mode') === 'test';
  }

  /**
   * {@inheritdoc}
   */
  public function setTokenRequestMode($mode = TRUE) {
    return $this->tokenRequestMode = $mode;
  }

  /**
   * {@inheritdoc}
   */
  public function isTokenRequestMode() {
    return $this->tokenRequestMode;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint() {
    return $this->isTestMode() ? $this->testEndpoint : $this->liveEndpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenEndpoint() {
    return $this->isTestMode() ? $this->testTokenEndpoint : $this->liveTokenEndpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getAudience() {
    return $this->testMode ? $this->testAudience : $this->testAudience;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientId() {
    return $this->clientId;
  }

  /**
   * {@inheritdoc}
   */
  public function setClientId($clientId) {
    $this->clientId = $clientId;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecret() {
    return $this->secret;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecret($secret) {
    $this->secret = $secret;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->token;
  }

  /**
   * {@inheritdoc}
   */
  public function setToken($token) {
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenExpiry() {
    return $this->tokenExpiry;
  }

  /**
   * {@inheritdoc}
   */
  public function setTokenExpiry($tokenExpiry) {
    $this->tokenExpiry = $tokenExpiry;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTokens() {
    $this->token = NULL;
    $this->tokenExpiry = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getReference() {
    return $this->getSettings('partpayRef');
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($key = NULL) {

    if (array_key_exists($key, $this->configuration)) {
      return $this->configuration[$key];
    };

    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function isRedirectMethod($response) {

    if (empty($response->redirectUrl)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl($response) {

    if (empty($response->redirectUrl)) {
      return '';
    }

    return $response->redirectUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function isSuccessful($response) {
    return $response->orderStatus === 'Approved';
  }

  /**
   * Create an access token.
   */
  public function createToken() {

    $this->setTokenRequestMode();

    $options = [
      RequestOptions::JSON => [
        "client_id" => $this->getClientId(),
        "client_secret" => $this->getSecret(),
        "audience" => $this->getAudience(),
        "grant_type" => "client_credentials",
      ],
    ];

    return $this->request('POST', '/oauth/token', $options);
  }

  /**
   * Get PartPay configuration.
   */
  public function getRemoteConfiguration() {
    return $this->request('GET', '/configuration');
  }

  /**
   * Create a PartPay order.
   */
  public function createOrder(array $transaction) {
    return $this->request('POST', '/order', [RequestOptions::JSON => $transaction]);
  }

  /**
   * Get order.
   */
  public function getOrder($id) {
    return $this->request('GET', '/order/' . $id);
  }

  /**
   * Refund all or part of a payment.
   */
  public function refundOrder($id, $body) {
    $options = [
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
      ],
      'body' => json_encode($body),
    ];
    return $this->request('POST', '/order/' . $id . '/refund', $options);
  }

  /**
   * {@inheritdoc}
   */
  public function request($method, $resource, array $options = []) {
    $headers = [];

    if ($this->hasToken()) {
      $headers = [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->getToken(),
        ],
      ];
    }

    $options = array_merge_recursive($headers, $options);

    if (!empty($options['query'])) {
      $options['query'] = http_build_query($options['query']);
    }

    return $this->handleRequest($method, $resource, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function handleRequest($method, $resource, array $options = []) {

    $endpoint = $this->getEndpoint();

    if ($this->isTokenRequestMode()) {
      $endpoint = $this->getTokenEndpoint();
    }

    $uri = $endpoint . $resource;

    try {
      $response = $this->httpClient->request($method, $uri, $options);

      $data = $response->getBody();

      return json_decode($data->getContents());

    }
    catch (RequestException $e) {
      return $e->getResponse();
    }
  }

}
