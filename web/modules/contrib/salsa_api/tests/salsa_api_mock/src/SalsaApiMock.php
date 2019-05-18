<?php

namespace Drupal\salsa_api_mock;

use Drupal\salsa_api\SalsaApi;

/**
 * Class SalsaAPIMock
 *
 * This is a mock implementation used for tests.
 *
 * @package Drupal\salsa_api_mock
 */
class SalsaApiMock extends SalsaApi {
  /**
   * Expected URL.
   *
   * @var string
   */
  protected $correctUrl;

  /**
   * Expected username.
   *
   * @var string
   */
  protected $correctUsername;

  /**
   * Expected password.
   *
   * @var string
   */
  protected $correctPassword;

  /**
   * {@inheritdoc}
   */
  public function testConnect($url, $username, $password) {
    $this->correctUrl = 'https://example.com';
    $this->correctUsername = 'user@example.com';
    $this->correctPassword = 'Correct password';

    if ($url != $this->correctUrl) {
      return static::CONNECTION_WRONG_URL;
    }
    elseif (($username == $this->correctUsername) && ($password == $this->correctPassword)) {
      return static::CONNECTION_OK;
    }
    else {
      return static::CONNECTION_AUTHENTICATION_FAILED;
    }
  }

}
