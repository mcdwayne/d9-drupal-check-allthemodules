<?php

namespace Drupal\social_post_linkedin;

use Drupal\social_post\PostManager\PostManager;

/**
 * Contains all the logic for LinkedIn OAuth2 authentication.
 */
class LinkedInPostAuthManager extends PostManager {

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
  public function getAuthorizationUrl() {
    $scopes = [
      'r_basicprofile',
      'r_emailaddress',
      'w_share',
    ];

    return $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
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
  public function getState() {
    return $this->client->getState();
  }

}
