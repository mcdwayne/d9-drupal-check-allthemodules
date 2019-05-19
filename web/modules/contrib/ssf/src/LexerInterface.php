<?php

namespace Drupal\ssf;

/**
 * Lexer interface.
 *
 * @package Drupal\ssf
 */
interface LexerInterface {

  /**
   * Splits a text to tokens.
   *
   * @param string $text
   *   The text.
   *
   * @return array
   *   Returns an associative array of tokens. The tokens are the keys of the
   *   array, and the array values are the count of occurences of a token in
   *   the text.
   */
  public function getTokens($text);

}
