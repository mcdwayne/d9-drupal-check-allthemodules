<?php

namespace Drupal\pocket;

class AccessToken {

  /**
   * @var string
   */
  private $token;

  /**
   * @var string
   */
  private $username;

  /**
   * @var array
   */
  private $state;

  /**
   * AccessToken constructor.
   *
   * @param string $token
   * @param string $username
   */
  public function __construct(string $token, string $username) {
    $this->token = $token;
    $this->username = $username;
  }

  /**
   * @return string
   */
  public function getToken(): string {
    return $this->token;
  }

  /**
   * @return string
   */
  public function getUsername(): string {
    return $this->username;
  }

  /**
   * @param array $state
   *
   * @return AccessToken
   */
  public function setState(array $state): AccessToken {
    $this->state = $state;
    return $this;
  }

  /**
   * @return array
   */
  public function getState(): array {
    return $this->state;
  }

}
