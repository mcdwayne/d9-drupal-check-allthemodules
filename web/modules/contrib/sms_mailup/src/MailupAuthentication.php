<?php

namespace Drupal\sms_mailup;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Url;
use Drupal\sms\Entity\SmsGateway;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

/**
 * The MailUp service.
 */
class MailupAuthentication implements MailupAuthenticationInterface {

  /**
   * The Drupal HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new MailUpService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function createOAuthProvider($gateway_id) {
    $gateway = SmsGateway::load($gateway_id);
    $configuration = $gateway->getPlugin()->getConfiguration();
    if (empty($configuration['oauth']['client_id']) || empty($configuration['oauth']['client_secret'])) {
      throw new \Exception('Missing oauth configuration client_id and/or client_secret.');
    }

    $client_id = $configuration['oauth']['client_id'];
    $client_secret = $configuration['oauth']['client_secret'];

    $redirect_uri = Url::fromRoute('sms_mailup.gateway.oauth.token.receive', ['sms_gateway' => $gateway_id])
      ->setAbsolute()->toString(TRUE)->getGeneratedUrl();

    // Create an OAuth provider.
    $options = [
      'clientId' => $client_id,
      'clientSecret' => $client_secret,
      'redirectUri' => $redirect_uri,
      'urlAuthorize' => 'https://services.mailup.com/Authorization/OAuth/Authorization',
      'urlAccessToken' => 'https://services.mailup.com/Authorization/OAuth/Token',
      // @todo change this
      'urlResourceOwnerDetails' => '',
    ];

    return (new GenericProvider($options))
      ->setHttpClient($this->httpClient);
  }

  /**
   * {@inheritdoc}
   */
  public function setState($gateway_id, $state) {
    $key = 'sms_mailup.oauth_token.' . $gateway_id;
    $value = [
      'state' => $state,
    ];
    \Drupal::state()->set($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getToken($gateway_id, $refresh = TRUE) {
    $token = $this->getTokenFromState($gateway_id);
    if (FALSE === $token) {
      return FALSE;
    }

    // Refresh token.
    if ($token->hasExpired() && $refresh) {
      $key = 'sms_mailup.oauth_token.' . $gateway_id;
      $existing_token = \Drupal::state()->get($key);

      $provider = $this->createOAuthProvider($gateway_id);
      $new_token = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $token->getRefreshToken(),
      ]);
      $this->setToken(
        $gateway_id,
        $existing_token['state'],
        $new_token->getToken(),
        $new_token->getRefreshToken(),
        $new_token->getExpires()
      );

      $token = $this->getTokenFromState($gateway_id);
    }

    return $token;
  }

  /**
   * Gets OAuth access from state.
   *
   * @param string $gateway_id
   *   A gateway entity ID.
   *
   * @return \League\OAuth2\Client\Token\AccessToken|FALSE
   *   The OAuth access token, or FALSE if it is not cached in state.
   */
  protected function getTokenFromState($gateway_id) {
    $key = 'sms_mailup.oauth_token.' . $gateway_id;

    if ($existing_token = \Drupal::state()->get($key)) {
      // The key may be exist, but only state exists. This can happen if the
      // authentication is still happening. So check for access token.
      if (isset($existing_token['token_current'])) {
        $options = [
          'access_token' => $existing_token['token_current'],
          'refresh_token' => $existing_token['token_refresh'],
          'expires' => $existing_token['expiration'],
        ];
        return new AccessToken($options);
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setToken($gateway_id, $state, $access_token, $refresh_token, $expiration) {
    $key = 'sms_mailup.oauth_token.' . $gateway_id;
    $existing_token = \Drupal::state()->get($key);
    if (isset($existing_token['state'])) {
      // State from the response needs to match the state we created from the
      // initial OAuth request so we know there no CSRF issues.
      if ($state == $existing_token['state']) {
        $value = [
          'state' => $state,
          'token_current' => $access_token,
          'token_refresh' => $refresh_token,
          'expiration' => $expiration,
        ];
        \Drupal::state()->set($key, $value);
      }
      else {
        // This request is forged, or another token was requested after this
        // authentication response was received.
        throw new \Exception('Gateway state mismatch.');
      }
    }
    else {
      // This should never happen.
      throw new \Exception('Gateway missing OAuth state.');
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function removeToken($gateway_id) {
    $key = 'sms_mailup.oauth_token.' . $gateway_id;
    \Drupal::state()->delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function expireToken($gateway_id) {
    $key = 'sms_mailup.oauth_token.' . $gateway_id;
    $existing_token = \Drupal::state()->get($key);
    if (isset($existing_token['expiration'])) {
      $existing_token['expiration'] = REQUEST_TIME - 1;
      \Drupal::state()->set($key, $existing_token);
    }
  }

}
