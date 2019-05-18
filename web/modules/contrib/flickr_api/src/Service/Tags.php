<?php

namespace Drupal\flickr_api\Service;

/**
 * Class Tags.
 *
 * @package Drupal\flickr_api\Service
 */
class Tags {

  /**
   * Client.
   *
   * @var \Drupal\flickr_api\Service\Client
   */
  protected $client;

  /**
   * Tags constructor.
   *
   * @param \Drupal\flickr_api\Service\Client $client
   *   Client.
   */
  public function __construct(Client $client) {
    // Flickr API Client.
    $this->client = $client;
  }

  /**
   * Get the popular tags for a given user.
   *
   * @param string $nsid
   *   NSID of the user whose tags will be returned.
   * @param string $count
   *   Number of tags to return.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.tags.getListUserPopular.
   *   (https://www.flickr.com/services/api/flickr.tags.getListUserPopular.html)
   */
  public function tagsGetListUserPopular($nsid, $count = NULL, $cacheable = TRUE) {
    $args = ['user_id' => $nsid];

    if ($count != NULL) {
      $args['count'] = $count;
    }

    $response = $this->client->request(
      'flickr.tags.getListUserPopular',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['who']['tags']['tag'];
    }

    return FALSE;
  }

  /**
   * Get the tag list for a given user.
   *
   * @param string $nsid
   *   NSID of the user whose photoset tags will be returned.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.tags.getListUser.
   *   (https://www.flickr.com/services/api/flickr.tags.getListUser.html)
   */
  public function tagsGetListUser($nsid, $cacheable = TRUE) {
    $args = [
      'user_id' => $nsid,
    ];

    $response = $this->client->request(
      'flickr.tags.getListUser',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['who']['tags']['tag'];
    }

    return FALSE;
  }

}
