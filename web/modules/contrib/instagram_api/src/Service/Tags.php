<?php

namespace Drupal\instagram_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Tags.
 *
 * @package Drupal\instagram_api\Service
 */
class Tags {

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
   * Tags constructor.
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
   * Get information about a tag object.
   *
   * @param string $tag
   *   Tag for which we need info.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.instagram.com/v1/tags/{tag-name}?access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/tags/
   */
  public function tagInfo($tag, $cacheable = TRUE) {
    $response = $this->client->request(
      'tags/' . $tag,
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

  /**
   * Get a list of recently tagged media.
   *
   * @param string $tag
   *   Tag for which we need info.
   * @param array $args
   *   Args, see API docs for options.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.instagram.com/v1/tags/{tag-name}/media/recent?access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/tags/
   */
  public function tagMediaRecent($tag, array $args = [], $cacheable = TRUE) {
    $response = $this->client->request(
      'tags/' . $tag . '/media/recent',
      $args,
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

  /**
   * Search for tags by name.
   *
   * @param string $query
   *   Query to search.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.instagram.com/v1/tags/search?q=snowy&access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/tags/
   */
  public function tagSearch($query, $cacheable = TRUE) {
    $response = $this->client->request(
      'tags/search',
      [
        'q' => $query,
      ],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
