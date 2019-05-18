<?php

/**
 * @file
 * Contains \Drupal\fences\Tests\StripWhitespaceTrait.
 */

namespace Drupal\fences\Tests;

trait StripWhitespaceTrait {
  /**
   * Remove HTML whitespace from a string.
   *
   * @param $string
   *   The input string.
   *
   * @return string
   *   The whitespace cleaned string.
   */
  protected function stripWhitespace($string) {
    $no_whitespace = preg_replace('/\s{2,}/', '', $string);
    $no_whitespace = str_replace("\n", '', $no_whitespace);
    return $no_whitespace;
  }
}
