<?php

/**
 * @file
 * Contains \Drupal\oauth\Authentication\Provider\OAuthProvider.
 */

namespace Drupal\oauth\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use \OauthProvider;
use \OauthException;
/**
 * Oauth authentication provider.
 */
class OAuthDrupalProvider implements AuthenticationProviderInterface {

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $user_data;

  /**
   * The logger service for OAuth.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * An authenticated user object.
   *
   * @var \Drupal\user\UserBCDecorator
   */
  protected $user;

  /**
   * Constructor.
   *
   * @param \Drupal\user\UserDataInterface
   *  The user data service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service for OAuth.
   */
  public function __construct(UserDataInterface $user_data, LoggerInterface $logger) {
    $this->user_data = $user_data;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // Only check requests with the 'authorization' header starting with OAuth.
    return preg_match('/^OAuth/', $request->headers->get('authorization'));
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    try {
      // Initialize and configure the OauthProvider too handle the request.
      $this->provider = new OAuthProvider();
      $this->provider->consumerHandler(array($this, 'lookupConsumer'));
      $this->provider->timestampNonceHandler(array($this, 'timestampNonceChecker'));
      $this->provider->tokenHandler(array($this, 'tokenHandler'));
      $this->provider->is2LeggedEndpoint(TRUE);

      // Now check the request validity.
      $this->provider->checkOAuthRequest();
    }
    catch (OAuthException $e) {
      // The OAuth extension throws an alert when there is something wrong
      // with the request (ie. the consumer key is invalid).
      $this->logger->warning($e->getMessage());
      return NULL;
    }

    // Check if we found a user.
    if (!empty($this->user)) {
      return $this->user;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {}

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    return FALSE;
  }

  /**
   * Finds a user associated with the OAuth crendentials given in the request.
   *
   * For the moment it handles two legged authentication for a pair of
   * dummy key and secret, 'a' and 'b' respectively.
   *
   * @param \OAuthProvider $provider
   *   An instance of OauthProvider with the authorization request headers.
   *
   * @return int
   *   - OAUTH_OK if the authentication was successful.
   *   - OAUTH_CONSUMER_KEY_UNKNOWN if not.
   *
   * @see http://www.php.net/manual/en/class.oauthprovider.php
   */
  public function lookupConsumer(OAuthProvider $provider) {
    $user_data = $this->user_data->get('oauth', NULL, $provider->consumer_key);
    if (!empty($user_data)) {
      $provider->consumer_secret = $user_data[key($user_data)]['consumer_secret'];
      $this->user = User::load(key($user_data));
      return OAUTH_OK;
    }
    else {
      return OAUTH_CONSUMER_KEY_UNKNOWN;
    }
  }

  /**
   * Token handler callback.
   *
   * @TODO this will be used in token authorization.
   */
  public function tokenHandler($provider) {
    return OAUTH_OK;
  }

  /**
   * Nonce handler.
   *
   * @TODO need to remember what the hell this was.
   */
  public function timestampNonceChecker($provider) {
    return OAUTH_OK;
  }

}
