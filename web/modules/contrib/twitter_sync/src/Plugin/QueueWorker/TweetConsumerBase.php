<?php

namespace Drupal\twitter_sync\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\Entity\Node;
use Drupal\twitter_sync\Twitter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TweetConsumerBase.
 */
abstract class TweetConsumerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Twitter object.
   *
   * @var \Drupal\twitter_sync\Twitter
   */
  protected $twitter;

  /**
   * TweetConsumerBase constructor. Adds Twitter object.
   *
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Twitter $twitter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->twitter = $twitter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twitter_sync')
    );
  }

  /**
   * Call twitter api.
   */
  protected function callTwitter() {
    $getfield = '?screen_name=';
    $getfield .= $this->twitter->getScreenName();
    $getfield .= '&exclude_replies=true';
    $getfield .= '&count=' . $this->twitter->getTweetCount();
    $getfield .= '&include_rts=false';
    $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
    $requestMethod = 'GET';
    $response = $this->twitter->setGetfield($getfield)
      ->buildOauth($url, $requestMethod)
      ->performRequest();

    if ($response) {
      $tweets = json_decode($response, TRUE);
      $tweet_id_config = 1;

      // In case of no more tweets since the last registered on config.
      if (count($tweets) > 0) {
        foreach ($tweets as $tweet) {
          $node = Node::create([
            'type'        => 'twitter_sync',
            'title'       => $tweet['id_str'],
            'field_twitter_sync_status_id' => $tweet['id_str'],
            'field_twitter_sync_screen_name' => $this->twitter->getScreenName(),
          ]);
          $node->save();
          $tweet_id_config++;
          // Limit only for 3 tweets.
          if ($tweet_id_config > 3) {
            break;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Security check to avoid PHP error.
    // Do nothing if we don't have our Twitter screen name defined.
    if (!empty($this->twitter->getScreenName())) {
      $this->callTwitter();
    }
  }

}
