<?php

/**
 * @file
 * Contains \Drupal\freshdesk_sso\AuthenticationService.
 */

namespace Drupal\freshdesk_sso;

use Drupal\freshdesk_sso\Entity\FreshdeskConfig;

/**
 * Class AuthenticationService.
 *
 * @package Drupal\freshdesk_sso
 */
class AuthenticationService {
  /**
   * @var FreshdeskConfig $desk
   */
  protected $desk;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->user = \Drupal::currentUser();
  }

  public function buildUrl(FreshdeskConfig $freshdesk_config) {
    return $freshdesk_config->domain() .
      '/login/sso/?name=' . urlencode($this->user->getDisplayName()) .
      '&email=' . urlencode($this->user->getEmail()) .
      '&timestamp=' . REQUEST_TIME . '&hash=' . $this->getHash($freshdesk_config);
  }

  private function getHash(FreshdeskConfig $freshdesk_config) {
    $data = $this->user->getDisplayName() . $this->user->getEmail() . REQUEST_TIME;
    return hash_hmac('md5', $data, $freshdesk_config->secret());
  }

}
