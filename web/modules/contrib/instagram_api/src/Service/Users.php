<?php

namespace Drupal\instagram_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Users.
 *
 * @package Drupal\instagram_api\Service
 */
class Users {

  /**
   * Client.
   *
   * @var \Drupal\instagram_api\Service\Client
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
   * @param \Drupal\instagram_api\Service\Client $client
   *   Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerChannelFactory.
   */
  public function __construct(Client $client,
                              LoggerChannelFactory $loggerFactory) {
    // Instagram API Client.
    $this->client = $client;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Get information about the owner of the access_token.
   *
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.instagram.com/v1/users/self/?access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/users/#get_users_self
   */
  public function getSelf($cacheable = TRUE) {
    $response = $this->client->request(
      'users/self',
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

  /**
   * Get the most recent media published by the owner of the access_token.
   *
   * @param array $args
   *   Args, see API docs for options.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.instagram.com/v1/users/self/?access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/users/#get_users_self
   */
  public function getSelfMediaRecent(array $args = [], $cacheable = TRUE) {
    $response = $this->client->request(
      'users/self/media/recent',
      $args,
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
