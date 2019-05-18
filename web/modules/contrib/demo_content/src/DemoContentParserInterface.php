<?php

/**
 * @file
 * Contains Drupal\demo_content\DemoContentParserInterface.
 */

namespace Drupal\demo_content;

/**
 * Interface DemoContentParserInterface
 *
 * @package Drupal\demo_content
 */
interface DemoContentParserInterface {

  /**
   * Parses a file and returns demo content values.
   *
   * @param $file_path
   *  The path to the file.
   * @param array $replacements
   *  An array of replacements to perform.
   * @return array An array of values from the file.
   * An array of values from the file.
   */
  public function parse($file_path, array $replacements = []);
}