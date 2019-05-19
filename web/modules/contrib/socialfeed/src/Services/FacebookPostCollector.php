<?php

namespace Drupal\socialfeed\Services;

use Facebook\Facebook;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FacebookPostCollector.
 *
 * @package Drupal\socialfeed
 */
class FacebookPostCollector {

  /**
   * Field names to retrieve from Facebook.
   *
   * @var array
   */
  protected $fields = [
    'link',
    'message',
    'created_time',
    'picture',
    'type',
  ];

  /**
   * Facebook application id.
   *
   * @var string
   */
  protected $appId;

  /**
   * Facebook application secret.
   *
   * @var string
   */
  protected $appSecret;

  /**
   * Facebook user token.
   *
   * @var string
   */
  protected $userToken;

  /**
   * Facebook permanent page token.
   *
   * @var string
   */
  protected $pagePermanentToken;

  /**
   * Facebook Client.
   *
   * @var \Facebook\Facebook
   */
  private $facebook;

  /**
   * Facebook page name.
   *
   * @var string
   */
  private $pageName;

  /**
   * FacebookPostCollector constructor.
   *
   * @param string $appId
   *   Facebook app id.
   * @param string $appSecret
   *   Facebook app secret.
   * @param string $userToken
   *   Facebook user token.
   * @param string $pageName
   *   Facebook page name.
   * @param \Facebook\Facebook|null $facebook
   *   Facebook client.
   *
   * @throws \Exception
   */
  public function __construct($appId, $appSecret, $userToken = NULL, $pageName = NULL, $facebook = NULL) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
    $this->userToken = $userToken;
    $this->pageName = $pageName;
    $this->facebook = $facebook;
    $this->setFacebookClient();
  }

  /**
   * Set the Facebook client.
   *
   * @throws \Facebook\Exceptions\FacebookSDKException
   */
  public function setFacebookClient() {
    if (NULL === $this->facebook) {
      $this->facebook = new Facebook([
        'app_id' => $this->appId,
        'app_secret' => $this->appSecret,
        'default_access_token' => $this->defaultAccessToken(),
        'default_graph_version' => 'v3.2',
      ]);
    }
  }

  /**
   * Fetch Facebook posts from a given feed.
   *
   * @param string $page_name
   *   The name of the page to fetch results from.
   * @param string $post_types
   *   The post types to filter for.
   * @param int $num_posts
   *   The number of posts to return.
   *
   * @return array
   *   An array of Facebook posts.
   *
   * @throws \Facebook\Exceptions\FacebookSDKException
   */
  public function getPosts($page_name, $post_types, $num_posts = 10) {
    $posts = [];
    $post_count = 0;
    $url = $page_name . $this->getFacebookFeedUrl($num_posts);
    do {
      $response = $this->facebook->get($url);
      // Ensure not caught in an infinite loop if there's no next page.
      $url = NULL;
      if ($response->getHttpStatusCode() == Response::HTTP_OK) {
        $data = json_decode($response->getBody(), TRUE);
        $posts = array_merge($this->extractFacebookFeedData($post_types, $data['data']), $posts);
        $post_count = count($posts);
        if ($post_count < $num_posts && isset($data['paging']['next'])) {
          $url = $data['paging']['next'];
        }
      }
    } while ($post_count < $num_posts || NULL != $url);
    return array_slice($posts, 0, $num_posts);
  }

  /**
   * Extract information from the Facebook feed.
   *
   * @param string $post_types
   *   The type of posts to extract.
   * @param array $data
   *   An array of data to extract information from.
   *
   * @return array
   *   An array of posts.
   */
  protected function extractFacebookFeedData($post_types, array $data) {
    $posts = array_map(function ($post) {
      return $post;
    }, $data);

    // Filtering needed.
    if (TRUE == is_string($post_types)) {
      $posts = array_filter($posts, function ($post) use ($post_types) {
        return $post['type'] === $post_types;
      });
      return $posts;
    }
    return $posts;
  }

  /**
   * Generate the Facebook access token.
   *
   * @return string
   *   The access token.
   */
  protected function defaultAccessToken() {

    $config = \Drupal::service('config.factory')->getEditable('socialfeed.facebooksettings');
    $permanent_token = $config->get('page_permanent_token');
    if (empty($permanent_token)) {
      $args = [
        'usertoken' => $this->userToken,
        'appid' => $this->appId,
        'appsecret' => $this->appSecret,
        'pageid' => $this->pageName,
      ];
      // Token.
      $graph_response = json_decode(file_get_contents("https://graph.facebook.com/v3.2/oauth/access_token?grant_type=fb_exchange_token&client_id={$args['appid']}&client_secret={$args['appsecret']}&fb_exchange_token={$args['usertoken']}"));
      $long_token = $graph_response->access_token;
      // User ID.
      $graph_response = json_decode(file_get_contents("https://graph.facebook.com/v3.2/me?access_token={$long_token}"));
      $user_id = $graph_response->id;
      // Page ID.
      $graph_response = json_decode(file_get_contents("https://graph.facebook.com/v3.2/{$args['pageid']}?fields=id&access_token={$long_token}"));
      $page_id = $graph_response->id;
      // Permanent Token.
      $graph_response = json_decode(file_get_contents("https://graph.facebook.com/v3.2/{$user_id}/accounts?access_token={$long_token}"));
      foreach ($graph_response->data as $response_data) {
        if ($response_data->id == $page_id) {
          $config->set('page_permanent_token', $response_data->access_token)->save();
          return $response_data->access_token;
        }
      }
    }
    else {
      return $permanent_token;
    }
  }

  /**
   * Create the Facebook feed URL.
   *
   * @param int $num_posts
   *   The number of posts to return.
   *
   * @return string
   *   The feed URL with the appended fields to retrieve.
   */
  protected function getFacebookFeedUrl($num_posts) {
    return '/feed?fields=' . implode(',', $this->fields) . '&limit=' . $num_posts;
  }

}
