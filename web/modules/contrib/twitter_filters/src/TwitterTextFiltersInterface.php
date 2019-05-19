<?php

namespace Drupal\twitter_filters;

/**
 * Interface TwitterTextFiltersInterface.
 *
 * @package Drupal\twitter_filters
 */
interface TwitterTextFiltersInterface {

  /**
   * Helper function to convert @ and # to links.
   *
   * This helper function converts Twitter-style @usernames and #hashtags into
   * actual links.
   *
   * @param string $text
   *   The text to be filtered.
   * @param string $prefix
   *   The string to search for.
   * @param string $destination
   *   The URL that the links will point to.
   * @param string $class
   *   An optional class to insert into the link.
   *
   * @return string
   *   The processed string.
   */
  public function twitterFilterText($text, $prefix, $destination, $class = '');

}
