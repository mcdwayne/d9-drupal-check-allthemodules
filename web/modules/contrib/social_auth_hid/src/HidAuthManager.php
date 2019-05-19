<?php

namespace Drupal\social_auth_hid;

use Drupal\social_auth\AuthManager\OAuth2Manager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Contains all the logic for Humanitarian ID OAuth2 authentication.
 */
class HidAuthManager extends OAuth2Manager {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Used for accessing configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ConfigFactory $configFactory, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configFactory->get('social_auth_hid.settings'), $logger_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    $this->setAccessToken($this->client->getAccessToken('authorization_code',
      ['code' => $_GET['code']]));
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo() {
    $this->user = $this->client->getResourceOwner($this->getAccessToken());
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl() {
    $scopes = [
      'profile',
    ];

    $hid_scopes = $this->getScopes();
    if ($hid_scopes) {
      if (strpos($hid_scopes, ',')) {
        $scopes = array_merge($scopes, explode(',', $hid_scopes));
      }
      else {
        $scopes[] = $hid_scopes;
      }
    }

    // Returns the URL where user will be redirected.
    return $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requestEndPoint($method, $path, $domain = NULL, array $options = Array()) {
    if (!$domain) {
      $domain = 'https://auth.humanitarian.id';
    }
    $url = $domain . $path;

    $request = $this->client->getAuthenticatedRequest($method, $url, $this->getAccessToken());

    $response = $this->client->getResponse($request);

    return $response->getBody()->getContents();
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->client->getState();
  }

}
