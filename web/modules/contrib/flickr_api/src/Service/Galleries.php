<?php

namespace Drupal\flickr_api\Service;

/**
 * Class Galleries.
 *
 * @package Drupal\flickr_api\Service
 */
class Galleries {

  /**
   * Client.
   *
   * @var \Drupal\flickr_api\Service\Client
   */
  protected $client;

  /**
   * Galleries constructor.
   *
   * @param \Drupal\flickr_api\Service\Client $client
   *   Client.
   */
  public function __construct(Client $client) {
    // Flickr API Client.
    $this->client = $client;
  }

  /**
   * Returns info about a given gallery.
   *
   * @param string $id
   *   NSID of the gallery whose photos you want.
   * @param array $otherArgs
   *   Additional args.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.gallery.getInfo.
   *   (https://www.flickr.com/services/api/flickr.gallery.getInfo.html)
   */
  public function galleriesGetInfo($id, array $otherArgs = [], $cacheable = TRUE) {
    $args = ['gallery_id' => $id];
    $args = array_merge($args, $otherArgs);

    $response = $this->client->request(
      'flickr.galleries.getInfo',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['gallery'];
    }

    return FALSE;
  }

  /**
   * Returns a list of photos for a given gallery.
   *
   * @param string $id
   *   ID of the gallery.
   * @param int $page
   *   Page.
   * @param array $otherArgs
   *   Other args.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.galleries.getPhotos.
   *   (https://www.flickr.com/services/api/flickr.galleries.getPhotos.html)
   */
  public function galleriesGetPhotos($id, $page = 1, array $otherArgs = [], $cacheable = TRUE) {
    $args = [
      'gallery_id' => $id,
      'page' => $page,
    ];
    $args = array_merge($args, $otherArgs);

    // Set per_page to flickr_api module default if not specified in $args.
    if (!isset($args['per_page'])) {
      // TODO Expose pager as a setting.
      $args['per_page'] = 6;
    }

    $response = $this->client->request(
      'flickr.galleries.getPhotos',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['photos'];
    }

    return FALSE;
  }

  /**
   * Returns the galleries curated by the specified user.
   *
   * @param string $nsid
   *   NSID of the user whose photoset list you want.
   * @param int $page
   *   Page.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.galleries.getList.
   *   (https://www.flickr.com/services/api/flickr.galleries.getList.html)
   */
  public function galleriesGetList($nsid, $page = 1, $cacheable = TRUE) {
    $args = [
      'user_id' => $nsid,
      'page' => $page,
    ];

    $response = $this->client->request(
      'flickr.galleries.getList',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['galleries']['gallery'];
    }

    return FALSE;
  }

}
