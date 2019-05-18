<?php
/**
 * @file
 * Contains Drupal\Tests\block_render\Unit\Libraries\Libraries.
 */

namespace Drupal\Tests\block_render\Unit\Libraries;

use Drupal\block_render\Libraries\Libraries;
use Drupal\Tests\UnitTestCase;

/**
 * Tests a set of libraries.
 *
 * @group block_render
 */
class LibrariesTest extends UnitTestCase {

  /**
   * Tests Libraries.
   */
  public function testLibraries() {
    $library = $this->getMockBuilder('Drupal\block_render\Library\LibraryInterface')
      ->getMock();

    new Libraries([$library, $library]);
  }

  /**
   * Tests Libraries Failure.
   */
  public function testLibrariesFailure() {
    $this->setExpectedException('\PHPUnit_Framework_Error');

    new Libraries(['string']);
  }

  /**
   * Tests setting a property on the class.
   */
  public function testSetFailure() {
    $this->setExpectedException('\LogicException', 'You cannot set properties.');

    $libraries = new Libraries();
    $libraries->libraries = ['some' => 'value'];
  }

  /**
   * Tests adding a library.
   */
  public function testsAddLibrary() {
    $library = $this->getMockBuilder('Drupal\block_render\Library\LibraryInterface')
      ->getMock();

    $libraries = new Libraries();

    $libraries->addLibrary($library);
    $libraries->addLibrary($library);

    $this->assertInternalType('array', $libraries->getLibraries());
    $this->assertArrayHasKey(0, $libraries->getLibraries());
    $this->assertEquals($library, $libraries->getLibraries()[0]);
    $this->assertArrayHasKey(1, $libraries->getLibraries());
    $this->assertEquals($library, $libraries->getLibraries()[1]);
  }

  /**
   * Tests getting a library.
   */
  public function testsGetLibraries() {
    $library = $this->getMockBuilder('Drupal\block_render\Library\LibraryInterface')
      ->getMock();

    $libraries = new Libraries([$library, $library]);

    $this->assertInternalType('array', $libraries->getLibraries());
    $this->assertArrayHasKey(0, $libraries->getLibraries());
    $this->assertEquals($library, $libraries->getLibraries()[0]);
    $this->assertArrayHasKey(1, $libraries->getLibraries());
    $this->assertEquals($library, $libraries->getLibraries()[1]);
  }

  /**
   * Tests getting the iterator.
   */
  public function testGetIterator() {
    $libraries = new Libraries();
    $this->assertInstanceOf('\ArrayIterator', $libraries->getIterator());
  }

}
