<?php

namespace Drupal\smugmug_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Image.
 *
 * @package Drupal\smugmug_api\Service
 */
class Image {

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
   * An image is a photo or video stored on SmugMug.
   *
   * @param string $imageId
   *   ImageID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.smugmug.com/api/v2/doc/reference/image.html
   *
   * @see https://api.smugmug.com/api/v2/image/jPPKD2c
   */
  public function getImage($imageId, $cacheable = TRUE) {
    $response = $this->client->request(
      'image/' . $imageId,
      [],
      $cacheable
    );

    if ($response) {
      return $response['Image'];
    }

    return FALSE;
  }

}
