<?php

namespace Drupal\google_api_client\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Google_Client;

/**
 * Class Google API Client Service.
 *
 * @package Drupal\google_api_client\Service
 */
class GoogleApiClient {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Uneditable Config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cacheBackend;

  /**
   * Editable Tokens Config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $configTokens;

  /**
   * Callback Controller constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   An instance of ConfigFactory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerChannelFactoryInterface.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache Backend.
   */
  public function __construct(ConfigFactory $config,
                              LoggerChannelFactoryInterface $loggerFactory,
                              CacheBackendInterface $cacheBackend) {
    $this->config = $config->get('google_api_client.settings');
    $this->configTokens = $config->getEditable('google_api_client.tokens');

    $this->loggerFactory = $loggerFactory;
    $this->cacheBackend = $cacheBackend;

    // Add the client without tokens.
    $this->googleClient = $this->getClient();

    // Check and add tokens.
    // Tokens wont always be set or valid, so this is a 2 step process.
    $this->setAccessToken();
  }

  /**
   * Google Client to Service.
   *
   * @return \Google_Client
   *   Google Client
   */
  private function getClient() {
    $client = new Google_Client();

    $credentials = Json::decode($this->config->get('credentials'));
    $client->setAuthConfig($credentials);

    // $client->setApplicationName('foobar');.
    // See https://developers.google.com/identity/protocols/googlescopes
    $scopes = array_map('trim', explode(PHP_EOL, $this->config->get('scopes')));
    $client->setScopes($scopes);
    // $client->setScopes(['https://www.googleapis.com/auth/plus.business.manage']);
    // For retrieving the refresh token.
    $client->setAccessType("offline");

    // Incremental auth.
    $client->setIncludeGrantedScopes(TRUE);

    // This is required when developing and in need of refresh tokens.
    // $client->setApprovalPrompt("force");.
    return $client;
  }

  /**
   * Wrapper for Google_Client::fetchAccessTokenWithAuthCode.
   *
   * @param string $code
   *   Code string from callback url.
   *
   * @return array
   *   Token values array.
   */
  public function getAccessTokenByAuthCode($code) {
    $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
    if (isset($token['access_token'])) {
      $this->setTokenCache('google_access_token', $token);
    }

    // Refresh token is only set the first time.
    if (isset($token['refresh_token'])) {
      $this->setTokenCache('google_refresh_token', [$token['refresh_token']]);
    }

    return $token;
  }

  /**
   * Wrapper for Google_Client::fetchAccessTokenWithRefreshToken.
   *
   * @return array|bool
   *   token array or false.
   */
  public function getAccessTokenWithRefreshToken() {
    // Get the refresh token from Cache. This is only send the first time.
    $refreshTokenCache = $this->getTokenCache('google_access_token');

    if (!empty($refreshTokenCache)) {
      $token = $this->googleClient->fetchAccessTokenWithRefreshToken($refreshTokenCache);
      if (isset($token['access_token'])) {
        $this->setTokenCache('google_access_token', $token);
        return $token;
      }
    }

    return FALSE;
  }

  /**
   * Google Token Cache Setter.
   *
   * @param string $key
   *   Cache ID.
   * @param array $value
   *   Cache Value.
   *
   * @return bool
   *   Status.
   */
  private function setTokenCache($key, array $value) {
    // Save the token.
    $this->configTokens
      ->set($key, serialize($value))
      ->save();

    return TRUE;
  }

  /**
   * Google Token Cache "config cache".
   *
   * @param string $type
   *   Cache ID.
   *
   * @return array
   *   Cache result.
   */
  private function getTokenCache($type) {
    // Check tokens in config "cache".
    if ($cache = $this->configTokens->get($type)) {
      $response = unserialize($cache);
      // Only validate if there are valid access tokens
      // Or if we have refresh token lookup.
      if (($type == 'google_access_token' && isset($response['access_token'])) || $type == 'google_refresh_token') {
        return $response;
      }
    }

    return [];
  }

  /**
   * Wrapper for Google_Client::setAccessToken.
   *
   * @return bool
   *   Was the token added or not?
   */
  private function setAccessToken() {
    // Set whatever token is in cache. So we can check its validity!
    $accessTokenCache = $this->getTokenCache('google_access_token');

    // If there was something in cache.
    if (!empty($accessTokenCache)) {
      $this->googleClient->setAccessToken($accessTokenCache);

      // Check if the current cached token is expired?
      if ($this->googleClient->isAccessTokenExpired()) {
        // Refresh the access token using refresh token.
        $tokenUpdated = $this->getAccessTokenWithRefreshToken();

        // Now that there is a new access token in cache,
        // set it into the client.
        if ($tokenUpdated != FALSE) {
          $this->googleClient->setAccessToken($tokenUpdated);
          // There should be a new unexpired token.
          return TRUE;
        }
        // Unable to update token.
        return FALSE;
      }
      // Token is set and is valid.
      return TRUE;
    }
    // There is no token cache.
    return FALSE;
  }

}
