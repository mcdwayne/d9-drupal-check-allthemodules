<?php

namespace Drupal\twitter_api;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Expands entities such as urls and images in tweets.
 */
class TwitterApiEntityExpander {

  /**
   * Expands urls in tweet text.
   *
   * @param array $tweet
   *   The tweet array from the API.
   *
   * @return Url[]
   *   An array of Url objects, keyed by the value in the text.
   */
  public function expandUrls(array $tweet) {
    if (empty($tweet['entities']['urls'])) {
      return [];
    }

    $replacements = [];
    foreach ($tweet['entities']['urls'] as $url_entity) {
      $replacements[$url_entity['url']] = Link::fromTextAndUrl($url_entity['display_url'], Url::fromUri($url_entity['expanded_url']));
    }

    return $replacements;
  }

  /**
   * Expands images in tweet text.
   *
   * @param array $tweet
   *   The tweet array from the API.
   *
   * @return string[]
   *   An array of image urls keyed by the url in the text.
   */
  public function expandImages(array $tweet) {
    if (empty($tweet['entities']['media'])) {
      return [];
    }

    $replacements = [];
    foreach ($tweet['entities']['media'] as $media_entity) {
      if ($media_entity['type'] != 'photo') {
        continue;
      }
      $replacements[$media_entity['url']] = $media_entity['media_url_https'];
    }

    return $replacements;
  }

}
