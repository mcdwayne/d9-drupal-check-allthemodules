<?php

namespace Drupal\url_to_video_filter\Service;

/**
 * Interface for the URL To Video service class.
 */
interface UrlToVideoFilterServiceInterface {

  /**
   * Converts URLs to embedded YouTube videos.
   *
   * @param string $text
   *   The text to be parsed for YouTube URLs.
   *
   * @return array
   *   An array containing the following keys:
   *   - text: The text with the URLs replaced by the YouTube embed code
   *   - url_found: A boolean indicating whether any URLs were found in
   *     the given text.
   */
  public function convertYouTubeUrls($text);

  /**
   * Converts URLs to embedded Vimeo videos.
   *
   * @param string $text
   *   The text to be parsed for Vimeo URLs.
   *
   * @return array
   *   An array containing the following keys:
   *   - text: The text with the URLs replaced by the YouTube embed code
   *   - url_found: A boolean indicating whether any URLs were found in
   *     the given text.
   */
  public function convertVimeoUrls($text);

}
