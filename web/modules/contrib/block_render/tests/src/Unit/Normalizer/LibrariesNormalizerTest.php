<?php
/**
 * @file
 * Contains Drupal\block_render\Normalizer\LibrariesNormalizer.
 */

namespace Drupal\Tests\block_render\Unit\Normalizer;

use Drupal\block_render\Normalizer\LibrariesNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * Test Libraries Normlizer.
 *
 * @group block_render
 */
class LibrariesNormalizerTest extends UnitTestCase {

  /**
   * Test the normalize method.
   */
  public function testNormalize() {
    $normalizer = new LibrariesNormalizer();

    $library = $this->getMockBuilder('Drupal\block_render\Library\LibraryInterface')
      ->getMock();

    $library->expects($this->once())
      ->method('getName')
      ->will($this->returnValue('test/test'));

    $library->expects($this->once())
      ->method('getVersion')
      ->will($this->returnValue('1.0.0'));

    $libraries = $this->getMockBuilder('Drupal\block_render\Libraries\LibrariesInterface')
      ->getMock();

    $libraries->expects($this->once())
      ->method('getIterator')
      ->will($this->returnValue(new \ArrayIterator([$library])));

    $data = $normalizer->normalize($libraries);

    $this->assertInternalType('array', $data);
    $this->assertArrayHasKey('test/test', $data);
    $this->assertEquals('1.0.0', $data['test/test']);
  }

  /**
   * Tests the normalization failure.
   */
  public function testNormalizeFailure() {
    $this->setExpectedException('\InvalidArgumentException', 'Object must implement Drupal\block_render\Libraries\LibrariesInterface');

    $normalizer = new LibrariesNormalizer();

    $rendered = $this->getMockBuilder('\Throwable')
      ->getMock();

    $normalizer->normalize($rendered);
  }

}
