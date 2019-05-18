<?php

namespace Drupal\flickr_api\Service;

/**
 * Class Photos.
 *
 * @package Drupal\flickr_api\Service
 */
class Photos {

  /**
   * Client.
   *
   * @var \Drupal\flickr_api\Service\Client
   */
  protected $client;

  /**
   * Photos constructor.
   *
   * @param \Drupal\flickr_api\Service\Client $client
   *   Client.
   */
  public function __construct(Client $client) {
    // Flickr API Client.
    $this->client = $client;
  }

  /**
   * Get information about a photo.
   *
   * @param string $photo_id
   *   ID of the photo to get info about.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response from the flickr method flickr.photos.getInfo..
   *   (https://www.flickr.com/services/api/flickr.photos.getInfo.html)
   */
  public function photosGetInfo($photo_id, $cacheable = TRUE) {
    $args = ['photo_id' => $photo_id];

    $response = $this->client->request(
      'flickr.photos.getInfo',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['photo'];
    }

    return FALSE;
  }

  /**
   * Returns the available sizes for a photo.
   *
   * @param string $photo_id
   *   ID of the photo to get the available sizes of.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response from the flickr method flickr.photos.getSizes..
   *   (https://www.flickr.com/services/api/flickr.photos.getSizes.html)
   */
  public function photosGetSizes($photo_id, $cacheable = TRUE) {
    $args = ['photo_id' => $photo_id];

    $response = $this->client->request(
      'flickr.photos.getSizes',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['sizes']['size'];
    }

    return FALSE;
  }

  /**
   * Return a list of photos matching some criteria.
   *
   * @param string $nsid
   *   NSID of the user whose photoset tags will be returned.
   * @param int $page
   *   Page of results to return.
   * @param array $otherArgs
   *   Other args.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response from the flickr method flickr.photos.search.
   *   (https://www.flickr.com/services/api/flickr.photos.search.html)
   */
  public function photosSearch($nsid, $page = 1, array $otherArgs = [], $cacheable = TRUE) {
    $args = [
      'page' => $page,
      'user_id' => $nsid,
    ];

    $args = array_merge($args, $otherArgs);

    // Set per_page to flickr_api module default if not specified in $args.
    if (!isset($args['per_page'])) {
      // TODO Expose pager as a setting.
      $args['per_page'] = 6;
    }

    $response = $this->client->request(
      'flickr.photos.search',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['photos'];
    }

    return FALSE;
  }

}
