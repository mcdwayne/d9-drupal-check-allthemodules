<?php

namespace Drupal\Tests\inmail\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Base class for Inmail unit tests.
 */
abstract class InmailUnitTestBase extends UnitTestCase {

  /**
   * Returns the raw contents of a given test message file.
   *
   * @param string $filename
   *   The name of the file.
   *
   * @return string
   *   The message content.
   */
  protected function getRaw($filename) {
    $path = __DIR__ . '/../../modules/inmail_test/eml/' . $filename;
    return file_get_contents($path);
  }

}
