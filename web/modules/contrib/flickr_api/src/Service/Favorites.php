<?php

namespace Drupal\flickr_api\Service;

/**
 * Class Favorites.
 *
 * @package Drupal\flickr_api\Service
 */
class Favorites {

  /**
   * Client.
   *
   * @var \Drupal\flickr_api\Service\Client
   */
  protected $client;

  /**
   * Favorites constructor.
   *
   * @param \Drupal\flickr_api\Service\Client $client
   *   Client.
   */
  public function __construct(Client $client) {
    // Flickr API Client.
    $this->client = $client;
  }

  /**
   * Returns a list of favorite public photos for the given user.
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
   * @return array
   *   Response from the flickr method flickr.favorites.getPublicList.
   *   (https://www.flickr.com/services/api/flickr.favorites.getPublicList.html)
   */
  public function favoritesGetPublicList($nsid, $page = 1, array $otherArgs = [], $cacheable = TRUE) {
    $args = [
      'user_id' => $nsid,
      'page' => $page,
    ];

    $args = array_merge($args, $otherArgs);

    // Set per_page to flickr_api module default if not specified in $args.
    if (!isset($args['per_page'])) {
      // TODO Expose pager as a setting.
      $args['per_page'] = 6;
    }

    $response = $this->client->request(
      'flickr.favorites.getPublicList',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['photos'];
    }

    return FALSE;
  }

}
