<?php

namespace Drupal\get_tweets;

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class RenderTweet.
 */
class RenderTweet {

  /**
   * The tweet.
   *
   * @var \stdClass
   */
  protected $tweet;

  /**
   * Content of tweet.
   *
   * @var string
   */
  protected $content;

  /**
   * RenderTweet constructor.
   *
   * @param \stdClass $tweet
   *   Tweet.
   */
  public function __construct(\stdClass $tweet) {
    $this->tweet = $tweet;
    $this->content = $tweet->full_text;
    if (isset($tweet->retweeted_status)) {
       $this->content = 'RT @'.$tweet->retweeted_status->user->screen_name.': '.$tweet->retweeted_status->full_text;
    }
  }

  /**
   * Return rendered tweet.
   *
   * @return string
   *   Rendered tweet.
   */
  public function build() {
    // Does not take into account media, hashtags, user_mentions or URLs of retweets
    !isset($this->tweet->entities->media) ?: $this->replaceMedia();
    !isset($this->tweet->entities->hashtags) ?: $this->replaceTags();
    !isset($this->tweet->entities->user_mentions) ?: $this->replaceUsers();
    !isset($this->tweet->entities->urls) ?: $this->replaceUrls();

    if (isset($this->tweet->retweeted_status)) {
      !isset($this->tweet->retweeted_status->entities->media) ?: $this->replaceRetweetMedia();
      !isset($this->tweet->retweeted_status->entities->hashtags) ?: $this->replaceRetweetTags();
      !isset($this->tweet->retweeted_status->entities->user_mentions) ?: $this->replaceRetweetUsers();
      !isset($this->tweet->retweeted_status->entities->urls) ?: $this->replaceRetweetUrls();
    }
    return $this->content;
  }

  /**
   * Creating link.
   *
   * @param string $text
   *   Text for replace.
   * @param string $uri
   *   Link for replace.
   *
   * @return \Drupal\Core\GeneratedLink
   *   Link object.
   */
  private function createLink($text, $uri) {
    $url = Url::fromUri($uri);
    $url->setOption('attributes', [
      'target' => '_blank',
    ]);

    return Link::fromTextAndUrl($text, $url)->toString();
  }

  /**
   * Replace entities in tweet.
   *
   * @param string $text
   *   Text for replace.
   * @param string $uri
   *   Link for replace.
   */
  private function entityReplace($text, $uri) {
    $link = $this->createLink($text, $uri);
    $this->content = str_replace($text, $link, $this->content);
  }

  /**
   * Replace hashtags.
   */
  private function replaceTags() {
    foreach ($this->tweet->entities->hashtags as $hashtag) {
      $this->entityReplace(
        "#" . $hashtag->text,
        "https://twitter.com/hashtag/" . $hashtag->text
      );
    }
  }

  /**
   * Replace retweet hashtags
   */

  private function replaceRetweetTags() {
    foreach ($this->tweet->retweeted_status->entities->hashtags as $hashtag) {
      $this->entityReplace(
        "#" . $hashtag->text,
        "https://twitter.com/hashtag/" . $hashtag->text
      );
    }
  }

  /**
   * Replace users.
   */
  private function replaceUsers() {
    foreach ($this->tweet->entities->user_mentions as $user) {
      $this->entityReplace(
        "@" . $user->screen_name,
        "https://twitter.com/" . $user->screen_name
      );
    }
  }

  /**
   * Replace retweeted users.
   */
  private function replaceRetweetUsers() {
    foreach ($this->tweet->retweeted_status->entities->user_mentions as $user) {
      $this->entityReplace(
        "@" . $user->screen_name,
        "https://twitter.com/" . $user->screen_name
      );
    }
  }

  /**
   * Replace urls.
   */
  private function replaceUrls() {
    foreach ($this->tweet->entities->urls as $url_value) {
      $this->entityReplace(
        $url_value->url,
        $url_value->url
      );
    }
  }

  /**
   * Replace retweeted urls.
   */
  private function replaceRetweetUrls() {
    foreach ($this->tweet->retweeted_status->entities->urls as $url_value) {
      $this->entityReplace(
        $url_value->url,
        $url_value->url 
      );
    }
  }

  /**
   * Replace media.
   */
  private function replaceMedia() {
    foreach ($this->tweet->entities->media as $url_value) {
      $this->entityReplace(
        $url_value->url,
        $url_value->url
      );
    }
  }

  /**
   * Replace retweeted media.
   */
  private function replaceRetweetMedia() {
    foreach ($this->tweet->retweeted_status->entities->media as $url_value) {
      $this->entityReplace(
        $url_value->url,
        $url_value->url 
      );
    }
  }
}
