<?php

namespace Drupal\eloqua_api_redux\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Eloqua API Client Service.
 *
 * @package Drupal\eloqua_api_redux\Service
 */
class EloquaApiClient {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Editable Tokens Config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $configTokens;

  /**
   * Uneditable Config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The HTTP client to fetch the API data.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * Callback Controller constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   An instance of ConfigFactory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerChannelFactoryInterface.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache Backend.
   * @param \Drupal\Core\Http\ClientFactory $httpClientFactory
   *   A Guzzle client object.
   */
  public function __construct(ConfigFactory $config,
                              LoggerChannelFactoryInterface $loggerFactory,
                              CacheBackendInterface $cacheBackend,
                              ClientFactory $httpClientFactory) {
    $this->config = $config->get('eloqua_api_redux.settings');
    $this->configTokens = $config->getEditable('eloqua_api_redux.tokens');
    $this->loggerFactory = $loggerFactory;
    $this->cacheBackend = $cacheBackend;
    $this->httpClientFactory = $httpClientFactory;
  }

  /**
   * Fetch Eloqua API Access Token by Auth Code.
   *
   * Use the Grant Token to obtain an Access Token and Refresh
   * Token using a POST request to the login.eloqua.com/auth/oauth2/token
   * endpoint.
   *
   * @param string $code
   *   Grant Token (which is in this case an Authorization Code).
   *
   * @return string|bool
   *   The authorization server validates the authorization code and if valid
   *   responds with a JSON body containing the Access Token, Refresh Token,
   *   access token expiration time, and token type
   */
  public function getAccessTokenByAuthCode($code) {
    if ($accessToken = $this->getEloquaApiCache('access_token')) {
      return $accessToken;
    }

    $params = [
      'redirect_uri' => Url::fromUri('internal:/eloqua_api_redux/callback', ['absolute' => TRUE])->toString(),
      'grant_type' => 'authorization_code',
      'code' => $code,
    ];

    $token = $this->doTokenRequest($params);

    if (!empty($token)) {
      return $token['access_token'];
    }

    return FALSE;
  }

  /**
   * Fetch Eloqua API Access Token by Refresh Token.
   *
   * If the access token has expired, you should send your stored Refresh Token
   * to login.eloqua.com/auth/oauth2/token to obtain new tokens.
   *
   * @return bool|mixed
   *   If the request is successful, the response is a JSON body containing
   *   a new access token, token type, access token expiration time, and
   *   new refresh token
   */
  public function getAccessTokenByRefreshToken() {
    if ($accessToken = $this->getEloquaApiCache('access_token')) {
      return $accessToken;
    }

    // Only do a request if we have a valid refresh token.
    if ($refreshToken = $this->getEloquaApiCache('refresh_token')) {
      // TODO Add better handling for expired refresh tokens.
      $params = [
        'redirect_uri' => Url::fromUri('internal:/eloqua_api_redux/callback', ['absolute' => TRUE])->toString(),
        'grant_type' => 'refresh_token',
        'refresh_token' => $refreshToken,
      ];

      $token = $this->doTokenRequest($params);

      if (!empty($token)) {
        return $token['access_token'];
      }
    }
    else {
      // If both access and refresh tokens are expired/invalid use fallback
      // method to generate access and refresh tokens using resource owner
      // password grant authorization.
      // Also make sure that auth fallback service implementation exists.
      $tokenGeneratorService = \Drupal::service('eloqua_api_redux.auth_fallback_default');
      if ($tokenGeneratorService !== NULL) {
        $response = $tokenGeneratorService->generateTokensByResourceOwner();
        if ($response === TRUE && $accessToken = $this->getEloquaApiCache('access_token')) {
          return $accessToken;
        }
      }
      $this->loggerFactory->get('eloqua_api_redux')
        ->error("Refresh Token is expired, Update tokens by visiting Eloqua API settings page.");
    }

    return FALSE;
  }

  /**
   * Do the Token Request.
   *
   * @param array $params
   *   Options to pass for Guzzle Request to Eloqua.
   * @param string $res
   *   Resource to call.
   *
   * @return bool|mixed
   *   If the request is successful, the response is a JSON body containing
   *   a new access token, token type, access token expiration time, and
   *   new refresh token
   */
  public function doTokenRequest(array $params, $res = 'token') {
    // Guzzle Client.
    $guzzleClient = $this->httpClientFactory->fromOptions([
      'base_uri' => $this->config->get('api_uri'),
    ]);

    $allParams = [
      'form_params' => $params,
      'auth' => [
        $this->config->get('client_id'),
        $this->config->get('client_secret'),
      ],
    ];

    try {
      $response = $guzzleClient->request('POST', $res, $allParams);

      if ($response->getStatusCode() == 200) {
        $contents = $response->getBody()->getContents();
        // TODO Add debugging options.
        // ksm(Json::decode($contents));
        $contentsDecoded = Json::decode($contents);

        // TODO Tokens are saved in config as a form of persistent storage.
        $this->setEloquaApiCache('access_token', $contentsDecoded['access_token']);
        $this->setEloquaApiCache('refresh_token', $contentsDecoded['refresh_token']);

        // Also update the base urls.
        $this->doBaseUrlRequest();

        return $contentsDecoded;
      }
    }
    catch (GuzzleException $e) {
      // TODO Add debugging options.
      // TODO Add better handling for expired refresh & access tokens.
      // ksm($e);
      $this->loggerFactory->get('eloqua_api_redux')->error("@message", ['@message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Get Cache Age.
   *
   * Authorization Codes expire in 60 seconds (intended for immediate use)
   * Access Tokens expire in 8 hours
   * Refresh Tokens expire in 1 year
   * Refresh Tokens will expire immediately after being used to obtain new
   * tokens, or after 1 year if they are not used to obtain new tokens.
   *
   * @param string $key
   *   What type of token is it?
   *
   * @return int
   *   Token age.
   */
  private function eloquaApiCacheAge($key) {
    $cacheAge = 0;

    // Offset a little so we can refresh before time.
    $offset = 3600;

    // Cache access_tokens and base URLs for same amount of time.
    if ($key == 'access_token' || $key == 'api_base_uri') {
      // Cache for 8 hours.
      $cacheAge = 28800;
    }
    if ($key == 'refresh_token') {
      // Cache for 1 year.
      $cacheAge = 31557600;
    }

    return $cacheAge - $offset;
  }

  /**
   * Get Base URL.
   *
   * @return false|string
   *   Base Url.
   */
  public function getBaseUrl() {
    if ($baseUrl = $this->getEloquaApiCache('api_base_uri')) {
      return $baseUrl;
    }

    // Not found in Cache, lets get it from source.
    $data = $this->doBaseUrlRequest();
    if (!empty($data)) {
      return $data['urls']['base'];
    }
  }

  /**
   * Determining base URLs.
   *
   * Eloqua supports multiple data centers, and the https://login.eloqua.com/id
   * endpoint allows you to interface with Eloqua regardless of where the
   * Eloqua install is located.
   *
   * It's important to validate your base URL before making any API calls.
   * If you don't validate, your API calls may not work. New Eloqua users may
   * be added to different data centers, and some Eloqua instances may
   * periodically move between data centers. There are many cases where the
   * base URL used for API access would change.
   *
   * The https://login.eloqua.com/id endpoint should be used to determine
   * the base URL for your API calls.
   *
   * See more details at:
   * https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/DeterminingBaseURL.html
   *
   * @return array
   *   The endpoint, when called using basic authentication or OAuth,
   *   will return details about the URLs you should be using.
   */
  private function doBaseUrlRequest() {
    // TODO Ideally merge all the Guzzle requests into one generic method.
    // Guzzle Client.
    $guzzleClient = $this->httpClientFactory->fromOptions([
      // TODO Move this into Config?
      'base_uri' => 'https://login.eloqua.com/',
      'headers' => [
        'Authorization' => 'bearer ' . $this->getAccessTokenByRefreshToken(),
      ],
    ]);

    try {
      $response = $guzzleClient->request('GET', 'id', []);
      if ($response->getStatusCode() == 200) {
        $contents = $response->getBody()->getContents();
        // TODO Add debugging options.
        // ksm(Json::decode($contents));
        $contentsDecoded = Json::decode($contents);
        // TODO Base Urls are saved in config as a form of persistent storage.
        $this->setEloquaApiCache('api_base_uri', $contentsDecoded['urls']['base']);
        return $contentsDecoded;
      }
    }
    catch (GuzzleException $e) {
      // TODO Add debugging options.
      // TODO Add better handling for expired refresh & access tokens.
      // ksm($e);
      $this->loggerFactory->get('eloqua_api_redux')->error("@message", ['@message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Eloqua API Cache Setter.
   *
   * @param string $key
   *   Cache ID.
   * @param string $value
   *   Cache Value.
   *
   * @return bool
   *   Status.
   */
  private function setEloquaApiCache($key, $value) {
    // Save the token.
    $cacheItem = [
      'value' => $value,
      'expire' => REQUEST_TIME + $this->eloquaApiCacheAge($key),
    ];

    $this->configTokens
      ->set($key, serialize($cacheItem))
      ->save();

    // TODO Maybe add some logging?
    return TRUE;
  }

  /**
   * Get Eloqua API cache from "config cache".
   *
   * @return string|false
   *   Cache result.
   */
  private function getEloquaApiCache($type) {
    // Check config "cache".
    if ($cache = $this->configTokens->get($type)) {
      $response = unserialize($cache);

      $now = REQUEST_TIME;
      $expire = $response['expire'];

      // Manually validate if the token is still fresh.
      if ($expire > $now) {
        // Return result from cache if found.
        return $response['value'];
      }
    }

    return FALSE;
  }

  /**
   * Make the request to Eloqua.
   *
   * @param string $verb
   *   GET POST etc.
   * @param string $endpoint
   *   Endpoint to call.
   * @param null $body
   *   Body for request.
   * @param null $queryParams
   *   Additional Params to pass.
   *
   * @return array
   *   Results.
   */
  public function doEloquaApiRequest($verb, $endpoint, $body = NULL, $queryParams = NULL) {
    // TODO Ideally merge all the Guzzle requests into one generic method.
    // Guzzle Client.
    $guzzleClient = $this->httpClientFactory->fromOptions([
      // TODO Move this into Config?
      'base_uri' => $this->getBaseUrl(),
      'headers' => [
        'Authorization' => 'bearer ' . $this->getAccessTokenByRefreshToken(),
        'Content-Type' => 'application/json',
      ],
    ]);

    $allParams = [];
    if ($body) {
      $allParams['body'] = json_encode($body);
    }
    if ($queryParams) {
      $allParams['query'] = $queryParams;
    }

    try {
      $response = $guzzleClient->request($verb, $endpoint, $allParams);
      // ksm($response);
      // See https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAB/Developers/GettingStarted/API%20requests/http-status-codes.htm?cshid=HTTPStatusCodes
      if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201 || $response->getStatusCode() == 204) {
        $contents = $response->getBody()->getContents();
        // TODO Add debugging options.
        // ksm($contents);
        // ksm(Json::decode($contents));
        $contentsDecoded = Json::decode($contents);
        return $contentsDecoded;
      }
    }
    catch (GuzzleException $e) {
      // TODO Add debugging options.
      // TODO Add better handling for expired refresh & access tokens.
      // ksm($e);
      $this->loggerFactory->get('eloqua_api_redux')->error("@message", ['@message' => $e->getMessage()]);
      return [];
    }
  }

}
