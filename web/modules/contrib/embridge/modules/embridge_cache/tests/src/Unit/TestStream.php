<?php
/**
 * @file
 * A unit testable stream.
 */

namespace Drupal\Tests\embridge_cache\Unit;

/**
 * Class TestStream.
 */
class TestStream {

  /**
   * A mock stream dir path.
   *
   * @return string
   *   The mock dir path.
   */
  public function getDirectoryPath() {
    return 'sites/default/files';
  }
}