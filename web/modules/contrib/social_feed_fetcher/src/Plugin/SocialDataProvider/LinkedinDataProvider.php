<?php

namespace Drupal\social_feed_fetcher\Plugin\SocialDataProvider;


use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\social_feed_fetcher\SocialDataProviderPluginBase;
use GuzzleHttp\Client;

/**
 * Class TwitterDataProvider
 *
 * @package Drupal\social_feed_fetcher\Plugin\SocialDataProvider
 *
 * @SocialDataProvider(
 *   id = "linkedin",
 *   label = @Translation("Linkedin data provider")
 * )
 */
class LinkedinDataProvider extends SocialDataProviderPluginBase {

  /**
   * Twitter OAuth client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $linkedin;

  /**
   * Feed used to get posts.
   *
   * @var string
   */
  protected $feed;

  /**
   * The companies Id to get update.
   *
   * @var string
   */
  protected $companiesId;

  /**
   * Set the Twitter client.
   */
  public function setClient() {
    if (NULL === $this->linkedin) {
      $this->linkedin = new Client([
        'base_uri' => 'https://www.linkedin.com',
        'allow_redirects' => FALSE,
        'timeout' => 0,
      ]);
    }
  }

  /**
   * @param $feed
   */
  public function setFeed($feed) {
    $this->feed = $feed;
  }

  /**
   * @param $companiesId
   */
  public function setCompaniesId($companiesId) {
    $this->companiesId = $companiesId;
  }

  /**
   * Retrieve Posts from the given accounts home page.
   *
   * @param int $count
   *   The number of posts to return.
   *
   * @return array
   *   An array of posts.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getPosts($count) {

    $bearer = \Drupal::service('state')->get('access_token');

    $feed = $this->feed . '/~';
    if ($this->feed == 'companies') {
      $feed = $this->feed . '/' . $this->companiesId . '/updates:(updateContent)';
    }

    $response = $this->linkedin->request(
      'GET',
      'v1/' . $feed,
      [
        'query' => [
          'format' => 'json'
        ],
        'headers' => [
          'Authorization' => 'Bearer ' . $bearer,
        ]
      ]
    );

    $data = $response->getBody()->getContents();
    $content = Json::decode($data);

    return $content;
  }

}
