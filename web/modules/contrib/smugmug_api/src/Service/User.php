<?php

namespace Drupal\smugmug_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class User.
 *
 * @package Drupal\smugmug_api\Service
 */
class User {

  /**
   * Client.
   *
   * @var \Drupal\smugmug_api\Service\Client
   */
  protected $client;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Media constructor.
   *
   * @param \Drupal\smugmug_api\Service\Client $client
   *   Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerChannelFactory.
   */
  public function __construct(Client $client,
                              LoggerChannelFactory $loggerFactory) {
    // SmugMug API Client.
    $this->client = $client;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * A user is a SmugMug user account.
   *
   * @param string $username
   *   Username.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.smugmug.com/api/v2/doc/reference/user.html
   *
   * @see https://api.smugmug.com/api/v2/user/cmac
   */
  public function getUser($username, $cacheable = TRUE) {
    $response = $this->client->request(
      'user/' . $username,
      [],
      $cacheable
    );

    if ($response) {
      return $response['User'];
    }

    return FALSE;
  }

}
