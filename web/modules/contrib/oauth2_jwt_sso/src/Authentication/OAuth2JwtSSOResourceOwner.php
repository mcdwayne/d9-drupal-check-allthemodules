<?php

namespace Drupal\oauth2_jwt_sso\Authentication;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;

class OAuth2JwtSSOResourceOwner implements ResourceOwnerInterface {
  protected $response;

  private $token;

  public function __construct(array $response, AccessToken $token) {
    $this->response = $response;
    $this->token = $token;
  }
  public function getId() {
    // TODO: Implement getId() method.
  }

  public function toArray() {
    return $this->response;
  }
}
