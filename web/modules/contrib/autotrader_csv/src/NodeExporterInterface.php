<?php

namespace Drupal\autotrader_csv;

/**
 * Interface NodeExporterInterface.
 *
 * @package Drupal\autotrader_csv
 */
interface NodeExporterInterface {

  /**
   * Generates the records and returns them as a string.
   *
   * @return string
   *   The string representation of the records separated by line breaks.
   */
  public function toString();

  /**
   * Generates the records and writes them to a file.
   *
   * Each record is written on its own line. It replaces current file contents.
   *
   * @return int
   *   The number of bytes that were written to the file, or FALSE on failure.
   */
  public function toFile();

}
