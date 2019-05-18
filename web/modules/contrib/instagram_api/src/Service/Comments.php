<?php

namespace Drupal\instagram_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Comments.
 *
 * @package Drupal\instagram_api\Service
 */
class Comments {

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
   * Get a list of recent comments on your media object.
   *
   * @param string $mediaId
   *   Media ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.instagram.com/v1/media/{media-id}/comments?access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/media/
   */
  public function getComments($mediaId, $cacheable = TRUE) {
    $response = $this->client->request(
      'media/' . $mediaId . '/comments',
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
