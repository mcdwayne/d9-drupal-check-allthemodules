<?php

namespace Drupal\Tests\porterstemmer\Unit;

/**
 * Provides a common method for testing the stemmer.
 */
trait TestItemsTrait {

  /**
   * Load an associative array of known input/output pairs.
   *
   *  This list comes from
   *  http://snowball.tartarus.org/algorithms/english/stemmer.html
   *  The array count is determined by parameters, below.
   *
   * @param int $skipto
   *   Line of file to start on (count starts at 0), not counting short ones.
   * @param int $runto
   *   Number of lines to test, not counting short ones.
   *
   * @return str[]
   *   An associative array of word=stem pairs where element [0] is the word
   *   and element [1] is the expected stem.
   */
  public function retrieveStemWords($skipto = 0, $runto = 5000) {
    $file = __DIR__ . '/testwords.txt';
    $handle = @fopen($file, "r");
    $tests = [];
    $skipped = 0;
    $ran = 0;

    while (!feof($handle) && $ran < $runto) {
      // Read a line of the file, and split into words.
      $line = trim(fgets($handle, 4096));
      $words = preg_split("/=/", $line, -1, PREG_SPLIT_NO_EMPTY);
      if (count($words) < 2) {
        continue;
      }
      $skipped++;
      if ($skipped < $skipto) {
        continue;
      }
      $tests[] = $words;
      $ran++;
    }

    fclose($handle);

    return $tests;
  }

}
