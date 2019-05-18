<?php
/**
 * @file
 * Contains Drupal\block_render\Normalizer\LibrariesNormalizer.
 */

namespace Drupal\block_render\Normalizer;

use Drupal\block_render\Normalizer\RenderedContentNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * Test the Rendered Content Normalizer.
 *
 * @group block_render
 */
class RenderedContentNormalizerTest extends UnitTestCase {

  /**
   * Tests the normalization.
   */
  public function testNormalize() {
    $normalizer = new RenderedContentNormalizer();

    $content = $this->getMockBuilder('Drupal\Component\Render\MarkupInterface')
      ->getMock();

    $content->expects($this->exactly(2))
      ->method('__toString')
      ->will($this->returnValue('content'));

    $rendered = $this->getMockBuilder('Drupal\block_render\Content\RenderedContentInterface')
      ->getMock();

    $rendered->expects($this->exactly(2))
      ->method('getIterator')
      ->will($this->returnValue(new \ArrayIterator(['test' => $content])));

    $rendered->expects($this->exactly(2))
      ->method('isSingle')
      ->will($this->onConsecutiveCalls(FALSE, TRUE));

    $data = $normalizer->normalize($rendered);

    $this->assertInternalType('array', $data);
    $this->assertArrayHasKey('test', $data);
    $this->assertInternalType('string', $data['test']);
    $this->assertEquals('content', $data['test']);

    $data = $normalizer->normalize($rendered);

    $this->assertInternalType('string', $data);
    $this->assertEquals('content', $data);
  }

  /**
   * Tests the normalization failure.
   */
  public function testNormalizeFailure() {
    $this->setExpectedException('\InvalidArgumentException', 'Object must implement Drupal\block_render\Content\RenderedContentInterface');

    $normalizer = new RenderedContentNormalizer();

    $rendered = $this->getMockBuilder('\Throwable')
      ->getMock();

    $normalizer->normalize($rendered);
  }

}
