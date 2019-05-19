<?php

namespace Drupal\strava\Api;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\strava_athletes\Entity\Athlete;
use League\OAuth2\Client\Token\AccessToken;
use Strava\API\Factory;

/**
 * Class Strava.
 *
 * Handles basic communication with the Strava library.
 *
 * @see \Strava\API\Factory
 *
 * @package Drupal\strava\Api
 */
class Strava implements StravaInterface {

  use MessengerTrait;

  /**
   * Strava module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Oauth2 client.
   *
   * @var \Strava\API\OAuth
   */
  protected $provider;

  /**
   * @var string
   */
  protected $accessToken;

  /**
   * @var \Strava\API\Factory
   */
  protected $factory;

  /**
   * @var \Strava\API\Client
   */
  protected $apiClient;

  /**
   * Strava constructor.
   */
  public function __construct() {

    $this->config = \Drupal::config('strava_configuration.settings');

    if (!empty($this->config->get('client_id')) && !empty($this->config->get('client_secret'))) {
      // Authenticate with provided module configuration.
      $this->factory = new Factory();
      $this->provider = $this->factory->getOAuthClient(
        $this->config->get('client_id'),
        $this->config->get('client_secret'),
        Url::fromRoute('strava.callback', [], ['absolute' => TRUE])
          ->toString()
      );
    }
    else {
      $this->messenger()
        ->addError(t('No Strava credentials were found, fill in the <a href="@config">Strava configuration form</a>.', ['@config' => Url::fromRoute('strava.configuration')]));
    }
  }

  /**
   * Authenticate the application and set an access token for API requests.
   *
   * @return bool
   */
  public function authenticate() {
    // Check if we received a code from Strava.
    $code = \Drupal::request()->query->get('code');
    if (!$code) {
      return FALSE;
    }

    // Get an access token.
    $this->accessToken = $this->getAccessToken($code);
    if (!$this->isAuthenticated()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check if this app is completely authenticated for API requests.
   *
   * @return bool
   */
  public function isAuthenticated() {
    if ($this->accessToken) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get an API client to perform API requests.
   */
  public function getApiClient() {
    if (!$this->apiClient) {
      // Initialize an API client.
      $this->apiClient = $this->factory->getAPIClient($this->accessToken);
    }

    return $this->apiClient;
  }

  /**
   * @param $athlete
   *
   * @return \Drupal\strava\Api\OAuth|\Strava\API\Client
   */
  public function getApiClientforAthlete($athlete) {
    if (is_numeric($athlete)) {
      $athlete = Athlete::load($athlete);
    }
    $uid = $athlete->getOwnerId();

    return $this->getApiClientForUser($uid);
  }

  /**
   * @param int $uid
   *
   * @return \Drupal\strava\Api\OAuth|\Strava\API\Client
   */
  public function getApiClientForUser($uid) {
    if ($token = $this->getUserAccessToken($uid)) {
      $this->setAccessToken($token);
      return $this->getApiClient();
    }
  }

  /**
   * Authorize function that creates the authorization link or gets the
   * authorization token.
   *
   * @return bool|string
   */
  public function getAuthorizationUrl() {
    if ($this->provider) {
      // Set the authorization scopes.
      $scope = $this->getScope();

      // Set scope in the provider object.
      $this->setScope($scope);

      // Return a full authorization url with
      return $this->provider->getAuthorizationUrl(['scope' => $scope]);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Set an array of authorization scopes.
   *
   * `public`: default, private activities are not returned, privacy zones are
   *   respected in stream requests.
   * `write`: modify activities, upload on the user’s behalf.
   * `view_private`: view private activities and data within privacy zones.
   * `view_private,write`:both ‘view_private’ and ‘write’ access.
   *
   * @param array $scope
   */
  public function setScope(array $scope) {
    $this->provider->scopes = $scope;
  }

  /**
   * Get configured authorization scopes or return the default.
   *
   * @return array
   */
  public function getScope() {
    // Get configured scopes.
    $scope = $this->config->get('scopes');
    if (is_string($scope)) {
      $scope = explode(',', $scope);
    }

    if (!$scope) {
      // Scopes defaults to 'write'.
      $scope = $this->provider->scopes;
    }

    return $scope;
  }

  /**
   * Check if there is an access token present in session data.
   *
   * @return bool
   */
  public function checkAccessToken() {
    /** @var \Drupal\core\TempStore\PrivateTempStore $session_data */
    $session_data = \Drupal::service('tempstore.private')->get('strava');
    $this->accessToken = $session_data->get('strava_access_token');

    if ($this->isAuthenticated()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Retrieve an access token from the API.
   *
   * @param string $code
   *   Optional authentication code to retrieve an access token from the api.
   *
   * @return string
   */
  public function getAccessToken($code = NULL) {
    if (is_string($code)) {
      $this->accessToken = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
      $this->storeAccessToken();
    }
    else {
      if (!$this->isAuthenticated()) {
        $this->checkAccessToken();
      }
    }

    if (!$this->isAuthenticated()) {
      $this->messenger()
        ->addError(t('Could not get access token, please retry authentication.'));
    }

    return $this->accessToken;
  }

  /**
   * Set a specific access token for an already authorised user.
   *
   * @param string $token
   */
  public function setAccessToken($token) {
    $this->accessToken = $token;
  }

  /**
   * Store access token in user's private session data.
   */
  public function storeAccessToken() {
    if (!$this->isAuthenticated()) {
      $this->authenticate();
    }
    try {
      $token = $this->accessToken;
      if ($token instanceof AccessToken) {
        $token = $token->getToken();
      }
      /** @var \Drupal\core\TempStore\PrivateTempStore $session_data */
      $session_data = \Drupal::service('tempstore.private')->get('strava');
      $session_data->set('strava_access_token', $token);
    }
    catch (TempStoreException $e) {
      $this->messenger()
        ->addError($e->getMessage());
    }
  }

  /**
   * Delete access token from user's private session data.
   */
  public function deleteAccessToken() {
    try {
      /** @var \Drupal\core\TempStore\PrivateTempStore $session_data */
      $session_data = \Drupal::service('tempstore.private')->get('strava');
      $session_data->delete('strava_access_token');
    }
    catch (TempStoreException $e) {
      $this->messenger()
        ->addError($e->getMessage());
    }
  }

  /**
   * Get a Strava access token from a user's private tempstore.
   *
   * @param int $uid
   *   User id.
   *
   * @return mixed
   */
  public function getUserAccessToken($uid) {
    $connection = \Drupal::database();
    $result = $connection->select('key_value_expire', 'kve')
      ->fields('kve', ['value'])
      ->condition('name', $uid . ':' . 'strava_access_token')
      ->execute();

    $token = $result->fetchCol();
    if (!empty($token) && is_array($token)) {
      $token = unserialize($token[0])->data;
    }
    return $token;
  }

}
