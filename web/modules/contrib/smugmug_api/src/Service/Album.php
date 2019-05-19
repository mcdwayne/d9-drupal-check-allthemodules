<?php

namespace Drupal\smugmug_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Album.
 *
 * @package Drupal\smugmug_api\Service
 */
class Album {

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
   * Get Album.
   *
   * The album endpoint provides access to
   * album settings and album contents.
   * Albums are also known as galleries.
   *
   * @param string $aid
   *   Album ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.smugmug.com/api/v2/doc/reference/album.html
   *
   * @see https://api.smugmug.com/api/v2/album/SJT3DX
   */
  public function getAlbum($aid, $cacheable = TRUE) {
    $response = $this->client->request(
      'album/' . $aid,
      [],
      $cacheable
    );

    if ($response) {
      return $response['Album'];
    }

    return FALSE;
  }

  /**
   * Get Album Image.
   *
   * An AlbumImage isn't an independent object.
   * Instead, it represents the relationship between a
   * particular album and a particular image in that album.
   * This is useful because the same image may appear in
   * multiple albums, such as by being collected into a second album.
   *
   * @param string $aid
   *   Album ID.
   * @param string $iid
   *   Image ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.smugmug.com/api/v2/doc/reference/album-image.html
   *
   * @see https://api.smugmug.com/api/v2/album/SJT3DX/image/jPPKD2c-1
   */
  public function getAlbumImage($aid, $iid, $cacheable = TRUE) {
    $response = $this->client->request(
      'album/' . $aid . '/image/' . $iid,
      [],
      $cacheable
    );

    if ($response) {
      return $response['AlbumImage'];
    }

    return FALSE;
  }

}
