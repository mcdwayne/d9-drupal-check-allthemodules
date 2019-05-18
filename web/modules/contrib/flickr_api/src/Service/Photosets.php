<?php

namespace Drupal\flickr_api\Service;

/**
 * Class Photosets.
 *
 * @package Drupal\flickr_api\Service
 */
class Photosets {

  /**
   * Client.
   *
   * @var \Drupal\flickr_api\Service\Client
   */
  protected $client;

  /**
   * Photosets constructor.
   *
   * @param \Drupal\flickr_api\Service\Client $client
   *   Client.
   */
  public function __construct(Client $client) {
    // Flickr API Client.
    $this->client = $client;
  }

  /**
   * Gets information about a photoset.
   *
   * @param string $photosetId
   *   ID of the photoset to get information about.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.photosets.getInfo.
   *   (https://www.flickr.com/services/api/flickr.photosets.getInfo.html)
   */
  public function photosetsGetInfo($photosetId, $cacheable = TRUE) {
    $args = ['photoset_id' => $photosetId];

    $response = $this->client->request(
      'flickr.photosets.getInfo',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['photoset'];
    }

    return FALSE;
  }

  /**
   * Returns the photosets belonging to the specified user.
   *
   * @param string $nsid
   *   NSID of the user whose photoset list you want.
   * @param int $page
   *   Page.
   * @param int $perPage
   *   Per Page.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.photosets.getList.
   *   (https://www.flickr.com/services/api/flickr.photosets.getList.html)
   */
  public function photosetsGetList($nsid, $page = NULL, $perPage = NULL, $cacheable = TRUE) {
    $args = [
      'user_id' => $nsid,
    ];

    if ($page != NULL) {
      $args['page'] = $page;
    }

    if ($perPage != NULL) {
      $args['per_page'] = $perPage;
    }

    $response = $this->client->request(
      'flickr.photosets.getList',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['photosets']['photoset'];
    }

    return FALSE;
  }

  /**
   * Get the list of photos in a set.
   *
   * @param string $photosetId
   *   The Flickr API photoset ID.
   * @param array $otherArgs
   *   Other args.
   * @param int $page
   *   Page.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.photosets.getPhotos.
   *   (https://www.flickr.com/services/api/flickr.photosets.getPhotos.html)
   */
  public function photosetsGetPhotos($photosetId, array $otherArgs = [], $page = 1, $cacheable = TRUE) {
    $args = [
      'photoset_id' => $photosetId,
      'page' => $page,
    ];

    $args = array_merge($args, $otherArgs);

    // Set per_page to flickr_api module default if not specified in $args.
    if (!isset($args['per_page'])) {
      // TODO Expose pager as a setting.
      $args['per_page'] = 6;
    }
    if (!isset($args['extras'])) {
      $args['extras'] = implode(',', $this->photosetsGetPhotosExtras());
    }

    $response = $this->client->request(
      'flickr.photosets.getPhotos',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['photoset'];
    }

    return FALSE;
  }

  /**
   * Get default extras options.
   */
  public function photosetsGetPhotosExtras() {
    $extras = [
      'license',
      'date_upload',
      'date_taken',
      'owner_name',
      'icon_server',
      'original_format',
      'last_update',
      'geo',
      'tags',
      'machine_tags',
      'o_dims',
      'views',
      'media',
      'path_alias',
      'url_sq',
      'url_t',
      'url_s',
      'url_m',
      'url_o',
    ];

    return $extras;
  }

}
