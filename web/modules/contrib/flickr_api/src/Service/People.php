<?php

namespace Drupal\flickr_api\Service;

/**
 * Class People.
 *
 * @package Drupal\flickr_api\Service
 */
class People {

  /**
   * Client.
   *
   * @var \Drupal\flickr_api\Service\Client
   */
  protected $client;

  /**
   * People constructor.
   *
   * @param \Drupal\flickr_api\Service\Client $client
   *   Client.
   */
  public function __construct(Client $client) {
    // Flickr API Client.
    $this->client = $client;
  }

  /**
   * Get information about a user.
   *
   * @param string $nsid
   *   The Flickr user's NSID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Array with person's info from flickr.people.getInfo.
   *   (https://www.flickr.com/services/api/flickr.people.getInfo.html)
   *   or FALSE on error.
   */
  public function peopleGetInfo($nsid, $cacheable = TRUE) {
    $args = [
      'user_id' => $nsid,
    ];

    $response = $this->client->request(
      'flickr.people.getInfo',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['person'];
    }

    return FALSE;
  }

  /**
   * Return a user's NSID, given their username.
   *
   * @param string $username
   *   Username to look for.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.people.findByUsername.
   *   (https://www.flickr.com/services/api/flickr.people.findByUsername.html)
   */
  public function peopleFindByUsername($username, $cacheable = TRUE) {
    $args = [
      'username' => $username,
    ];

    $response = $this->client->request(
      'flickr.people.findByUsername',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['user'];
    }

    return FALSE;
  }

  /**
   * Return a user's NSID, given their alias.
   *
   * @param string $alias
   *   Username to look for.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.people.findByUsername.
   *   (https://www.flickr.com/services/api/flickr.people.findByUsername.html)
   */
  public function peopleFindByAlias($alias, $cacheable = TRUE) {
    $args = [
      'url' => 'https://www.flickr.com/photos/' . $alias,
    ];

    $response = $this->client->request(
      'flickr.people.findByUsername',
      $args,
      $cacheable
    );

    if ($response && $response['stat'] == 'ok') {
      return $response['user'];
    }

    return FALSE;
  }

  /**
   * Return a user's NSID, given their email address.
   *
   * @param string $email
   *   Email to look for.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.people.findByEmail.
   *   (https://www.flickr.com/services/api/flickr.people.findByEmail.html)
   */
  public function peopleFindByEmail($email, $cacheable = TRUE) {
    $args = [
      'find_email' => $email,
    ];

    $response = $this->client->request(
      'flickr.people.findByEmail',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['user'];
    }

    return FALSE;
  }

  /**
   * Returns a list of public photos for the given user.
   *
   * @param string $nsid
   *   NSID of the user whose photos you want.
   * @param int $page
   *   Page.
   * @param array $otherArgs
   *   Other args.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array
   *   Response from the flickr method flickr.people.getPublicPhotos.
   *   (https://www.flickr.com/services/api/flickr.people.getPublicPhotos.html)
   */
  public function peopleGetPublicPhotos($nsid, $page = 1, array $otherArgs = [], $cacheable = TRUE) {
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
      'flickr.people.getPublicPhotos',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['photos'];
    }

    return FALSE;
  }

}
