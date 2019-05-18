<?php

namespace Drupal\get_tweets;

use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class GetTweetsImport.
 */
class GetTweetsBase {

  /**
   * Drupal logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */

  protected $logger;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * The GetTweets settings.
   *
   * @var \Drupal\Core\Config\ConfigInterface
   */
  protected $getTweetsSettings;

  /**
   * Twitter connection object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $connection;

  /**
   * Constructs a GetTweetsBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactory $logger) {
    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->getTweetsSettings = $config_factory->get('get_tweets.settings');
    $this->logger = $logger->get('get_tweets');
  }

  /**
   * Returns TwitterOAuth object or null.
   *
   * @param string $consumer_key
   *   The Application Consumer Key.
   * @param string $consumer_secret
   *   The Application Consumer Secret.
   * @param string|null $oauth_token
   *   The Client Token (optional).
   * @param string|null $oauth_token_secret
   *   The Client Token Secret (optional).
   *
   * @return \Abraham\TwitterOAuth\TwitterOAuth|null
   *   Returns TwitterOAuth object or null.
   */
  public function getConnection($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $connection = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);

    if ($connection) {
      return $connection;
    }

    return NULL;
  }

  /**
   * Import tweets.
   */
  public function import() {
    $config = $this->getTweetsSettings;
    $connection = $this->getConnection($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('oauth_token'), $config->get('oauth_token_secret'));

    if (!$config->get('import') && !$connection) {
      return;
    }

    $count = $config->get('count');

    foreach ($config->get('queries') as $query) {
      $parameters = [
        $query['parameter'] => $query['query'],
        "count" => $count,
        "tweet_mode" => 'extended',
        "include_entities" => TRUE,
      ];

      $endpoint = $query['endpoint'];

      $max_id_query = $this->nodeStorage->getAggregateQuery();
      $max_id_query->condition('field_tweet_author.title', trim($query['query'], '@'));
      $max_id_query->aggregate('field_tweet_id', 'MAX');
      $result = $max_id_query->execute();

      if (isset($result[0]['field_tweet_id_max'])) {
        $parameters['since_id'] = $result[0]['field_tweet_id_max'];
      }

      $tweets = $connection->get($endpoint, $parameters);

      if (isset($connection->getLastBody()->errors)) {
        $this->logger->error($connection->getLastBody()->errors[0]->message);
        return;
      }

      if ($endpoint == 'search/tweets') {
        $tweets = $tweets->statuses;
      }

      if ($tweets && empty($tweets->errors)) {
        foreach ($tweets as $tweet) {
          $this->createNode($tweet, $endpoint, $query['query']);
        }
      }
    }
  }

  /**
   * Creating node.
   *
   * @param \stdClass $tweet
   *   Tweet for import.
   * @param string $tweet_type
   *   Tweet type.
   * @param string $query_name
   *   Query name.
   */
  public function createNode(\stdClass $tweet, $tweet_type = 'statuses/user_timeline', $query_name = '') {
    $render_tweet = new RenderTweet($tweet);

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'tweet',
      'field_tweet_id' => $tweet->id,
      'field_tweet_author' => [
        'uri' => $tweet_type == 'statuses/user_timeline' ? 'https://twitter.com/' . $tweet->user->screen_name : 'https://twitter.com/search?q=' . str_replace('#', '%23', $query_name),
        'title' => $tweet_type == 'statuses/user_timeline' ? $tweet->user->screen_name : $query_name,
      ],
      'title' => 'Tweet #' . $tweet->id,
      'field_tweet_content' => [
        'value' => $render_tweet->build(),
        'format' => 'full_html',
      ],
      'created' => strtotime($tweet->created_at),
      'uid' => '1',
      'status' => 1,
    ]);

    if (isset($tweet->entities->user_mentions)) {
      foreach ($tweet->entities->user_mentions as $user_mention) {
        $node->field_tweet_mentions->appendItem($user_mention->screen_name);
      }
    }

    if (isset($tweet->entities->hashtags)) {
      foreach ($tweet->entities->hashtags as $hashtag) {
        $node->field_tweet_hashtags->appendItem($hashtag->text);
      }
    }

    if (isset($tweet->entities->media)) {
      foreach ($tweet->entities->media as $media) {
        if ($media->type == 'photo') {
          $node->set('field_tweet_external_image', $media->media_url);
          $path_info = pathinfo($media->media_url_https);
          $data = file_get_contents($media->media_url_https);
          $dir = 'public://tweets/';
          if ($data && file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
            $file = file_save_data($data, $dir . $path_info['basename'], FILE_EXISTS_RENAME);
            $node->set('field_tweet_local_image', $file);
          }
        }
      }
    }

    if (isset($tweet->retweeted_status)) {
      if (isset($tweet->retweeted_status->entities->user_mentions)) {
        foreach ($tweet->retweeted_status->entities->user_mentions as $user_mention) {
          if (!self::check_duplicate_users($node->field_tweet_mentions,$user_mention->screen_name)) {
            $node->field_tweet_mentions->appendItem($user_mention->screen_name);
          }
        }
      }
      if (isset($tweet->retweeted_status->entities->hashtags)) {
        foreach ($tweet->retweeted_status->entities->hashtags as $hashtag) {
          if (!self::check_duplicate_hashtags($node->field_tweet_hashtags,$hashtag->text)) {
            $node->field_tweet_hashtags->appendItem($hashtag->text);
          }
        }
      }
      if (isset($tweet->retweeted_status->entities->media)) {
        foreach ($tweet->retweeted_status->entities->media as $media) {
          if ($media->type == 'photo') {
            $node->set('field_tweet_external_image', $media->media_url);
            $path_info = pathinfo($media->media_url_https);
            $data = file_get_contents($media->media_url_https);
            $dir = 'public://tweets/';
            if ($data && file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
              $file = file_save_data($data, $dir . $path_info['basename'], FILE_EXISTS_RENAME);
              $node->set('field_tweet_local_image', $file);
            }
          }
        }
      }
    }

    $node->save();
  }

  /**
   * Check if user_mention or hashtag already exists. 
   */
  public function check_duplicate_users($users,$tweetuser) {
    foreach($users AS $user) {
      if ($user == $tweetuser) {
         return TRUE;
      }
    }
    return FALSE;
  }

  public function check_duplicate_hashtags($hashtags,$tweethash) {
    foreach($hashtags AS $hashtag) {
      if ($hashtag == $tweethash) {
         return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Run all tasks.
   */
  public function runAll() {
    $this->import();
    $this->cleanup();
  }

  /**
   * Delete old tweets.
   */
  public function cleanup() {
    $config = $this->getTweetsSettings;
    $expire = $config->get('expire');

    if (!$expire) {
      return;
    }

    $storage = $this->nodeStorage;
    $query = $storage->getQuery();
    $query->condition('created', time() - $expire, '<');
    $query->condition('type', 'tweets');
    $result = $query->execute();
    $nodes = $storage->loadMultiple($result);

    foreach ($nodes as $node) {
      $node->delete();
    }
  }

}
