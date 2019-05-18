<?php
/**
 * @file
 * Contains Drupal\Tests\block_render\Unit\Library\Library.
 */

namespace Drupal\Tests\block_render\Unit\Library;

use Drupal\block_render\Library\Library;
use Drupal\Tests\UnitTestCase;

/**
 * Single Library Tests.
 *
 * @group block_render
 */
class LibraryTest extends UnitTestCase {

  /**
   * Get the Library Name test.
   */
  public function testGetName() {
    $library = new Library('test/test', '1.0.0');

    $name = $library->getName();
    $this->assertInternalType('string', $name);
    $this->assertEquals('test/test', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetVersion() {
    $library = new Library('test/test', '1.0.0');

    $version = $library->getVersion();
    $this->assertInternalType('string', $version);
    $this->assertEquals('1.0.0', $version);
  }

}
