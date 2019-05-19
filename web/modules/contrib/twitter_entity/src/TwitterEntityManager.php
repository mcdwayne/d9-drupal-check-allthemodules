<?php

namespace Drupal\twitter_entity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Component\Utility\Xss;
use Drupal\twitter_entity\Entity\TwitterEntity;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class TwitterEntityManager.
 *
 * @package Drupal\twitter_entity
 */
class TwitterEntityManager {
  use StringTranslationTrait;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a TwitterEntityManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Tries to pull latest Tweets.
   *
   * @return array|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Error if API keys are incorrect or number of created Tweets.
   */
  public function pull() {
    $config = $this->configFactory->get('twitter_entity.settings');

    // Init connection with twitter API.
    $connection = new TwitterOAuth(
      $config->get('consumer_key'),
      $config->get('consumer_secret'),
      $config->get('oauth_access_token'),
      $config->get('oauth_access_token_secret')
    );

    // Check if provided API information are correct.
    $accountVerify = $connection->get("account/verify_credentials");
    if (isset($accountVerify->errors)) {
      return [
        'error' => $this->t('Incorrect API keys information. Go to twitter settings page and provide correct API keys.'),
      ];
    }

    // Get all provided users that we need to pull tweets from.
    $twitterUserNames = explode(PHP_EOL, $config->get('twitter_user_names'));
    $tweetsCreated = 0;

    foreach ($twitterUserNames as $userName) {
      $tweets = $connection->get('statuses/user_timeline', [
        'screen_name' => $userName,
        'count' => $config->get('tweets_number_per_request'),
        'exclude_replies' => TRUE,
      ]);

      // Loop trough each tweet and save it into database if don't exist.
      foreach ($tweets as $tweet) {
        $tweetExist = TwitterEntity::loadByTweetId($tweet->id);
        if (!$tweetExist) {
          // Make sure we don't have any weird characters before saving,
          // full response in database.
          if (isset($tweet->text)) {
            $tweet->text = $this::removeEmoji($tweet->text);
          }
          if (isset($tweet->retweeted_status->text)) {
            $tweet->retweeted_status->text = $this::removeEmoji($tweet->retweeted_status->text);
          }

          $tweetMediaUrl = '';
          if (isset($tweet->entities->media[0]->media_url)) {
            $tweetMediaUrl = $tweet->entities->media[0]->media_url;
          }

          $newTweet = TwitterEntity::create([
            'created' => date("U", strtotime($tweet->created_at)),
            'tweet_id' => $tweet->id,
            'tweet_media' => $tweetMediaUrl,
            'tweet_text' => $this::addLinksToTweet($tweet->text),
            'twitter_user' => $userName,
            'full_response' => $tweet,
          ]);
          $newTweet->save();

          $tweetsCreated++;
        }
      }

    }

    if ($tweetsCreated > 0) {
      return $this->t(
        '@tweetsCreated new tweets was created.',
        ['@tweetsCreated' => $tweetsCreated]
      );
    }

    return $this->t('No new tweets created.');
  }

  /**
   * Automatically add links to URLs and Twitter user names in a tweet.
   *
   * @param string $text
   *   Raw text without html markup.
   *
   * @return string
   *   Text with html links added to it.
   */
  public static function addLinksToTweet($text) {
    $pattern = '#(https?)://([^\s\(\)\,]+)#ims';
    $replace = '<a href="$1://$2" rel="nofollow" target="_blank" title="$1://$2">$2</a>';
    $text = preg_replace($pattern, $replace, $text);

    $pattern = '#@(\w+)#ims';
    $replace = '<a href="http://twitter.com/$1" rel="nofollow" target="_blank" title="@$1" class="tweet-author">@$1</a>';
    $text = preg_replace($pattern, $replace, $text);

    $pattern = '/[#]+([A-Za-z0-9-_]+)/';
    $replace = '<a href="http://twitter.com/#!/search?q=%23$1" target="_blank" title="#$1" rel="nofollow">#$1</a>';
    $text = preg_replace($pattern, $replace, $text);

    return Xss::filter($text);
  }

  /**
   * Removes special characters from text.
   *
   * @param string $text
   *   Text to process.
   *
   * @return string
   *   Text with special characters removed (if there was some of them).
   */
  public static function removeEmoji($text) {
    return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
  }

}
