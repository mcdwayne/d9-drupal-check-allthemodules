<?php

namespace Drupal\media_acquiadam;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;

/**
 * OAuth Class.
 */
class Oauth implements OauthInterface {

  /**
   * The base URL to use for the DAM API.
   *
   * @var string
   */
  protected $damApiBase = "https://apiv2.webdamdb.com";

  /**
   * The media_acquiadam configuration.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * A URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * An HTTP client.
   *
   * @var \Guzzle\Http\ClientInterface
   */
  protected $httpClient;

  /**
   * Destination URI after authentication is completed.
   *
   * @var string
   */
  protected $authFinishRedirect;

  /**
   * Oauth constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfTokenGenerator
   *   The CSRF Token generator.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The URL generator.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP guzzle Client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CsrfTokenGenerator $csrfTokenGenerator, UrlGeneratorInterface $urlGenerator, ClientInterface $httpClient) {
    $this->config = $config_factory->get('media_acquiadam.settings');
    $this->csrfTokenGenerator = $csrfTokenGenerator;
    $this->urlGenerator = $urlGenerator;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthLink() {
    $client_id = $this->config->get('client_id');
    $token = $this->csrfTokenGenerator->get('media_acquiadam.oauth');
    $redirect_uri = $this->urlGenerator->generateFromRoute('media_acquiadam.auth_finish', ['auth_finish_redirect' => $this->authFinishRedirect], ['absolute' => TRUE]);

    return "{$this->damApiBase}/oauth2/authorize?response_type=code&state={$token}&redirect_uri={$redirect_uri}&client_id={$client_id}";
  }

  /**
   * {@inheritdoc}
   */
  public function authRequestStateIsValid($token) {
    return $this->csrfTokenGenerator->validate($token, 'media_acquiadam.oauth');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($auth_code) {
    \Drupal::logger('media_acquiadam')
      ->debug('Getting new access token for @username.', [
        '@username' => \Drupal::currentUser()
          ->getAccountName(),
      ]);

    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $this->httpClient->post("{$this->damApiBase}/oauth2/token", [
      'form_params' => [
        'grant_type' => 'authorization_code',
        'code' => $auth_code,
        'redirect_uri' => $this->urlGenerator->generateFromRoute('media_acquiadam.auth_finish', ['auth_finish_redirect' => $this->authFinishRedirect], ['absolute' => TRUE]),
        'client_id' => $this->config->get('client_id'),
        'client_secret' => $this->config->get('secret'),
      ],
    ]);

    $body = (string) $response->getBody();
    $body = json_decode($body);

    return [
      'access_token' => $body->access_token,
      'expire_time' => time() + $body->expires_in,
      'refresh_token' => $body->refresh_token,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function refreshAccess($refresh_token) {

    \Drupal::logger('media_acquiadam')
      ->debug('Refreshing access token for @username.', [
        '@username' => \Drupal::currentUser()
          ->getAccountName(),
      ]);

    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $this->httpClient->post("{$this->damApiBase}/oauth2/token", [
      'form_params' => [
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token,
        'client_id' => $this->config->get('client_id'),
        'client_secret' => $this->config->get('secret'),
        'redirect_uri' => $this->urlGenerator->generateFromRoute('media_acquiadam.auth_finish', ['auth_finish_redirect' => $this->authFinishRedirect], ['absolute' => TRUE]),
      ],
    ]);

    $body = (string) $response->getBody();
    $body = json_decode($body);

    return [
      'access_token' => $body->access_token,
      'expire_time' => time() + $body->expires_in,
      'refresh_token' => $body->refresh_token,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthFinishRedirect($authFinishRedirect) {
    $parsed_url = UrlHelper::parse($authFinishRedirect);

    $filterable_keys = \Drupal::config('media_acquiadam.settings')->get('oauth.excluded_redirect_keys');
    if (empty($filterable_keys) || !is_array($filterable_keys)) {
      $filterable_keys = [
        // The Entity Browser Block module will break the authentication flow
        // when used within Panels IPE. Filtering out this query parameter
        // works around the issue.
        'original_path',
      ];
    }

    $this->authFinishRedirect = Url::fromUri('base:' . $parsed_url['path'], [
      'query' => UrlHelper::filterQueryParameters($parsed_url['query'], $filterable_keys),
      'fragment' => $parsed_url['fragment'],
    ])->toString();
  }

  /**
   * Gets the auth_finish_redirect url.
   *
   * @return mixed
   *   Url string if is set, null if not set.
   */
  public function getAuthFinishRedirect() {
    if (isset($this->authFinishRedirect)) {
      return $this->authFinishRedirect;
    }
    else {
      return NULL;
    }
  }

}
