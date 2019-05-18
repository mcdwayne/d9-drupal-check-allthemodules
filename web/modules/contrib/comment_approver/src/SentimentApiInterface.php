<?php

namespace Drupal\comment_approver;

/**
 * Interface SentimentApiInterface.
 */
interface SentimentApiInterface {
  /**
   * Neutral text.
   */
  const NEUTRAL = 0;
  /**
   * Positive text.
   */
  const POSITIVE = 1;
  /**
   * Negative text.
   */
  const NEGATIVE = -1;

  /**
   * Performs the sentiment analysis on a text.
   *
   * @param string $text
   *   The text on which sentiment analysis will be performed.
   * @param string $language
   *   The language of the text, defaults to english.
   *
   * @return int
   *   Returns 0 if neutral,positive if analysis is positive and negative if
   *   analyis is negative
   */
  public function test(string $text, string $language = 'english');

}
