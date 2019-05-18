<?php

namespace Drupal\flickr_api\Service;

/**
 * Class Groups.
 *
 * @package Drupal\flickr_api\Service
 */
class Groups {

  /**
   * Client.
   *
   * @var \Drupal\flickr_api\Service\Client
   */
  protected $client;

  /**
   * Groups constructor.
   *
   * @param \Drupal\flickr_api\Service\Client $client
   *   Client.
   * @param \Drupal\flickr_api\Service\Helpers $helpers
   *   Helpers.
   */
  public function __construct(Client $client, Helpers $helpers) {
    // Flickr API Client.
    $this->client = $client;

    // Flickr API Helpers.
    $this->helpers = $helpers;
  }

  /**
   * Returns info about a given group.
   *
   * @param string $id
   *   NSID of the group whose photos you want.
   * @param array $otherArgs
   *   Other args.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.groups.getInfo.
   *   (https://www.flickr.com/services/api/flickr.groups.getInfo.html)
   */
  public function groupsGetInfo($id, array $otherArgs = [], $cacheable = TRUE) {
    if ($this->helpers->isNsid($id)) {
      $args = ['group_id' => $id];
    }
    else {
      $args = ['group_path_alias' => $id];
    }

    $args = array_merge($args, $otherArgs);

    $response = $this->client->request(
      'flickr.groups.getInfo',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['group'];
    }

    return FALSE;
  }

}
