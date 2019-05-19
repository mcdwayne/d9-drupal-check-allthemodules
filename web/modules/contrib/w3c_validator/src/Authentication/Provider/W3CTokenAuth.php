<?php

namespace Drupal\w3c_validator\Authentication\Provider;


use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\w3c_validator\W3CTokenManager;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Oauth authentication provider.
 */
class W3CTokenAuth implements AuthenticationProviderInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A W3cTokenManager instance
   *
   * @var \Drupal\w3c_validator\W3CTokenManager
   */
  protected $w3cTokenManager;

  /**
   * Constructs a W3CSubscriber object.
   *
   * @param \Drupal\w3c_validator\W3CTokenManager $w3c_token_manager
   *   The form builder service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(W3CTokenManager $w3c_token_manager, LoggerInterface $logger) {
    $this->w3cTokenManager = $w3c_token_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // Only check requests with the 'authorization' header starting with OAuth.
    $token = $request->query->get('HTTP_W3C_VALIDATOR_TOKEN');
    return isset($token);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {

    $token = $request->query->get('HTTP_W3C_VALIDATOR_TOKEN');

    // If a token is there.
    if (!empty($token) && $user = $this->w3cTokenManager->getUserFromToken($token)) {

      // Retrieve the current accessed URL.
      $current_url = Url::fromRoute('<current>');
      // Log the access.
      $this->logger->notice('Request to validate private page @url using token @token for user @user', ['@url' => $current_url->toString(), '@token' => $token, '@user' => $user->label()]);

      return $user;
    }

    return NULL;
  }
}
