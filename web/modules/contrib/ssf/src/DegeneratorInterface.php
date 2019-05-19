<?php

namespace Drupal\ssf;

/**
 * Degenerator interface.
 *
 * @package Drupal\ssf
 */
interface DegeneratorInterface {

  /**
   * Generates a list of "degenerated" words for a list of words.
   *
   * @param array $words
   *   The array item values are strings that represent tokens/words.
   *
   * @return array
   *   An array containing an array of degenerated tokens for each token.
   *   The tokens are the keys of the array and the item value is an array of
   *   degenerated tokens (strings) for that token.
   */
  public function degenerate(array $words);

  /**
   * Get the degenerates for a token.
   *
   * @param string $token
   *   The word.
   *
   * @return array
   *   An array of degenerated tokens (strings).
   */
  public function getDegenerates($token);

}
