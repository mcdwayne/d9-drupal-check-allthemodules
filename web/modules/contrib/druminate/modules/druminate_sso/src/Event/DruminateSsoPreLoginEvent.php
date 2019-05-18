<?php

namespace Drupal\druminate_sso\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Notify event listeners about a Druminate SSO Login Event.
 *
 * @package Drupal\druminate_sso\Event
 */
class DruminateSsoPreLoginEvent extends Event {

  /**
   * The LO SSO Token.
   *
   * @var string
   */
  protected $token;

  /**
   * The LO SSO nonce.
   *
   * @var string
   */
  protected $nonce;

  /**
   * The LO SSO Constituent ID.
   *
   * @var string
   */
  protected $consId;

  /**
   * The module providing authentication.
   *
   * @var string
   */
  protected $provider;

  /**
   * The username or email of the user logging in.
   *
   * @var string
   */
  protected $authname;

  /**
   * Prevents the user from logging in.
   *
   * @var bool
   */
  protected $authRestricted;

  /**
   * The Drupal user logging in.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * DruminateExternalAuthLoginEvent constructor.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account logging in.
   * @param string $provider
   *   The name of the service providing external authentication.
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $token
   *   The SSO Token from a successful LO login response.
   * @param string $nonce
   *   The nonce from a successful LO login response.
   * @param string $cons_id
   *   The constituent ID from a successful LO login response.
   */
  public function __construct(UserInterface $account, $provider, $authname, $token, $nonce, $cons_id) {
    $this->token = $token;
    $this->nonce = $nonce;
    $this->consId = $cons_id;
    $this->provider = $provider;
    $this->authname = $authname;
    $this->authRestricted = FALSE;
    $this->account = $account;
  }

  /**
   * Gets the SSO token.
   *
   * @return string
   *   The SSO token.
   */
  public function getToken() {
    return $this->token;
  }

  /**
   * Gets the SSO nonce.
   *
   * @return string
   *   The LO API nonce.
   */
  public function getNonce() {
    return $this->nonce;
  }

  /**
   * Gets the SSO Constituent ID.
   *
   * @return string
   *   The constituent ID.
   */
  public function getConsId() {
    return $this->consId;
  }

  /**
   * Gets the module providing authentication.
   *
   * @return string
   *   The module provider.
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * Gets the authentication name/email address.
   *
   * @return string
   *   The account name/email used to log in.
   */
  public function getAuthname() {
    return $this->authname;
  }

  /**
   * Sets the authRestricted property.
   *
   * Event listeners will use this to prevent the user from logging in.
   *
   * @param bool $restrict
   *   Whether or not to restrict authentication.
   */
  public function setAuthRestricted($restrict) {
    $this->authRestricted = $restrict;
  }

  /**
   * Gets the authRestricted property.
   *
   * @return bool
   *   The authRestricted property value.
   */
  public function isAuthRestricted() {
    return $this->authRestricted;
  }

  /**
   * Gets the user account.
   *
   * @return \Drupal\user\UserInterface
   *   The user account logging in.
   */
  public function getAccount() {
    return $this->account;
  }

}
