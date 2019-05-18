<?php

namespace Drupal\formassembly;

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\State\StateInterface as State;
use Psr\Log\LoggerInterface;
use Fathershawn\OAuth2\Client\Provider\FormAssembly as OauthProvider;

/**
 * Service class for FormAssembly API: Handles authorization.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2018 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @package Drupal\formassembly
 */
class ApiAuthorize extends ApiBase {

  /**
   * Drupal State storage for Token.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Service to obtain oauth credentials.
   *
   * @var \Drupal\formassembly\FormAssemblyKeyService
   */
  protected $keyService;

  /**
   * ApiAuthorize constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Injected ConfigFactory service.
   * @param \Psr\Log\LoggerInterface $loggerChannel
   *   Injected Logger service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Injected State service.
   * @param \Drupal\formassembly\FormAssemblyKeyService $keyService
   *   Injected Key service.
   */
  public function __construct(
    ConfigFactory $config_factory,
    LoggerInterface $loggerChannel,
    State $state,
    FormAssemblyKeyService $keyService
  ) {
    parent::__construct($config_factory, $loggerChannel);
    $this->state = $state;
    $this->keyService = $keyService;
  }

  /**
   * Use the auth code provided by FormAssembly to authorize the application.
   *
   * @param string $code
   *   The authorization code returned by FormAssembly.
   */
  public function authorize($code) {
    // Configure the request:
    $provider = $this->getOauthProvider();

    // Try to get an access token using the authorization code grant.
    try {
      $accessToken = $provider->getAccessToken(
        'authorization_code',
        [
          'code' => $code,
        ]
      );
      $this->state->set('fa_form.access_token', $accessToken);
    }
    catch (\Exception $e) {
      $this->logger->critical(
        'FormAssembly authorization request failed with Exception: %exception_type.',
        ['%exception_type' => get_class($e)]
      );
    }
  }

  /**
   * Retrieve an active access_token.
   *
   * Either returns the current valid token or if the expire date is passed
   * then the renewal process is used to obtain a new token.
   *
   * @return string
   *   An access token if available or an empty string.
   *
   * @throws \Exception
   *   Any exception passed through from the Oauth request.
   */
  public function getToken() {
    /** @var \League\OAuth2\Client\Token\AccessToken $accessToken */
    $accessToken = $this->state->get('fa_form.access_token');
    if ($accessToken->hasExpired()) {
      // Get the provider.
      $provider = $this->getOauthProvider();
      // Try to get an access token using the authorization code grant.
      try {
        $newAccessToken = $provider->getAccessToken(
          'refresh_token',
          [
            'refresh_token' => $accessToken->getRefreshToken(),
          ]
        );
        $this->state->set('fa_form.access_token', $newAccessToken);
      }
      catch (\Exception $e) {
        $this->logger->critical(
          'FormAssembly new token request failed with Exception: %exception_type.',
          ['%exception_type' => get_class($e)]
        );
        throw $e;
      }
      return $newAccessToken->getToken();
    }
    return $accessToken->getToken();
  }

  /**
   * Checks for a valid authorization token.
   *
   * @return bool
   *   If there is such a valid token.
   */
  public function isAuthorized() {
    /** @var \League\OAuth2\Client\Token\AccessToken $accessToken */
    $accessToken = $this->state->get('fa_form.access_token');
    return isset($accessToken) && !$accessToken->hasExpired();
  }

  /**
   * Configures and returns an OAuth provider for FormAssembly.
   *
   * @return \Fathershawn\OAuth2\Client\Provider\FormAssembly
   *   The configured provider.
   */
  protected function getOauthProvider() {
    $credentials = $this->keyService->getOauthKeys();
    $provider = new OauthProvider(
      [
        'clientId' => $credentials['cid'],
        'clientSecret' => $credentials['secret'],
        'redirectUri' => Url::fromRoute(
          'fa_form.authorize.store',
          [],
          ['absolute' => TRUE]
        )->toString(TRUE)->getGeneratedUrl(),
        'baseUrl' => $this->getUrl('base')->toString(TRUE)->getGeneratedUrl(),
      ]
    );
    return $provider;
  }

}
