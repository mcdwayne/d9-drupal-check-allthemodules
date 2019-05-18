<?php

namespace Drupal\psn_public_trophies\Component;

use PSNPublicTrophiesLib\PSNPublicTrophiesAuth;
use PSN\AuthException;

use PSN\User;

/**
 * Provides methods for generating path aliases.
 */
class DrupalPSNPublicTrophies {
  public $token;
  private $config;
  private $psnPublicTrophiesAuth;

  /**
   * Initialize __construct().
   */
  public function __construct() {
    $this->config = \Drupal::service('config.factory')->getEditable('psn_public_trophies.settings');

    $this->token = $this->config->get('psn_account_token', NULL);

    $this->psnPublicTrophiesAuth = new PSNPublicTrophiesAuth($this->token);
    if ($this->token) {
      $this->config->set('psn_account_token', $this->psnPublicTrophiesAuth->access_token)->save();
      $this->token = $this->psnPublicTrophiesAuth->access_token;
    }
  }

  /**
   * Method getAuthorizationUrl().
   */
  public function getAuthorizationUrl() {
    return $this->psnPublicTrophiesAuth->getAuthenticateUrl();
  }

  /**
   * Method connect().
   */
  public function connect($psn_id, $password, $ticket_uuid_url = NULL, $verification_code = NULL) {
    try {
      $auth_url_parameters = $this->psnPublicTrophiesAuth->getAuthenticateUrlParameters($ticket_uuid_url);
      $ticket_uuid = NULL;
      if (isset($auth_url_parameters['ticket_uuid'])) {
        $ticket_uuid = $auth_url_parameters['ticket_uuid'];
      }

      $auth = $this->psnPublicTrophiesAuth->authenticate($psn_id, $password, $ticket_uuid, $verification_code);

      $psn_account_token = $auth->GetTokens();

      $this->config->set('psn_account_token', $psn_account_token)->save();
      $this->token = $psn_account_token;

      return $psn_account_token;
    }
    catch (AuthException $e) {
      throw $e;
    }
  }

  /**
   * Method disconnect().
   */
  public function disconnect() {
    $this->config->set('psn_account_token', NULL)->save();
    $this->token = NULL;
    return NULL;
  }

  /**
   * Method refreshToken().
   */
  public function refreshToken() {
    $new_token = $this->psnPublicTrophiesAuth->refreshToken();
    $this->config->set('psn_account_token', $new_token)->save();
    $this->token = $new_token;

    return $new_token;
  }

  /**
   * Method getUser().
   */
  public function getUser() {
    try {
      $this->refreshToken();
      return new User($this->token);
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Method getUserMe().
   */
  public function getUserMe() {
    return $this->psnPublicTrophiesAuth->getProfile();
  }

  /**
   * Method getMyTrophies().
   */
  public function getMyTrophies() {
    return $this->psnPublicTrophiesAuth->getTrophies();
  }

  /**
   * Method getFriends().
   */
  public function getFriends() {
    return $this->psnPublicTrophiesAuth->getFriend();
  }

  /**
   * Method getTrophy().
   */
  public function getTrophy() {
    return $this->psnPublicTrophiesAuth->getTrophy();
  }

}
