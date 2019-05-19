<?php

namespace Drupal\social_auth_uber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_auth\AuthManager\OAuth2Manager;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Contains all the logic for Uber OAuth2 authentication.
 */
class UberAuthManager extends OAuth2Manager {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Used for accessing configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($config_factory->get('social_auth_uber.settings'), $logger_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    try {
      $this->setAccessToken($this->client->getAccessToken('authorization_code',
        ['code' => $_GET['code']]));
    }
    catch (IdentityProviderException $e) {
      $this->loggerFactory->get('social_auth_uber')
        ->error('There was an error during authentication. Exception: ' . $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo() {
    if (!$this->user) {
      $this->user = $this->client->getResourceOwner($this->getAccessToken());
    }

    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl() {
    $scopes = ['profile'];

    $extra_scopes = $this->getScopes();
    if ($extra_scopes) {
      $scopes = array_merge($scopes, explode(',', $extra_scopes));
    }

    // Returns the URL where user will be redirected.
    return $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requestEndPoint($method, $path, $domain = NULL, array $options = []) {
    $domain = $domain ?: 'https://api.uber.com';

    $url = $domain . $path;

    $request = $this->client->getAuthenticatedRequest($method, $url, $this->getAccessToken());

    try {
      return $this->client->getParsedResponse($request);
    }
    catch (IdentityProviderException $e) {
      $this->loggerFactory->get('social_auth_uber')
        ->error('There was an error when requesting ' . $url . '. Exception: ' . $e->getMessage());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->client->getState();
  }

}
