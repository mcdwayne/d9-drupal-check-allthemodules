<?php
/**
 * @file
 * Contains Drupal\Tests\block_render\Unit\Normalizer\BlockResponseNormalizer.
 */

namespace Drupal\Tests\block_render\Unit\Normalizer;

use Drupal\block_render\Normalizer\BlockResponseNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for normalizing the block response.
 *
 * @group block_render
 */
class BlockResponseNormalizerTest extends UnitTestCase {

  /**
   * Tests the normalization method.
   */
  public function testNormalize() {
    $serializer = $this->getMockBuilder('Drupal\block_render\Normalizer\NormalizerSerializerInterface')
      ->getMock();
    $serializer->expects($this->exactly(2))
      ->method('normalize')
      ->will($this->returnValue('normalized object'));

    $normalizer = new BlockResponseNormalizer();
    $normalizer->setSerializer($serializer);

    $assets = $this->getMockBuilder('Drupal\block_render\Response\AssetResponseInterface')
      ->getMock();
    $assets->expects($this->exactly(1))
      ->method('getHeader')
      ->will($this->returnValue('header array'));
    $assets->expects($this->exactly(1))
        ->method('getFooter')
        ->will($this->returnValue('footer array'));

    $response = $this->getMockBuilder('Drupal\block_render\Response\BlockResponseInterface')
      ->getMock();

    $response->expects($this->exactly(3))
      ->method('getAssets')
      ->will($this->returnValue($assets));

    $data = $normalizer->normalize($response);

    $this->assertInternalType('array', $data);
    $this->assertArrayHasKey('dependencies', $data);
    $this->assertEquals('normalized object', $data['dependencies']);
    $this->assertArrayHasKey('assets', $data);
    $this->assertArrayHasKey('header', $data['assets']);
    $this->assertEquals('header array', $data['assets']['header']);
    $this->assertArrayHasKey('footer', $data['assets']);
    $this->assertEquals('footer array', $data['assets']['footer']);
    $this->assertArrayHasKey('content', $data);
    $this->assertEquals('normalized object', $data['content']);
  }

  /**
   * Tests the normalization failure.
   */
  public function testNormalizeFailure() {
    $this->setExpectedException('\InvalidArgumentException', 'Object must implement Drupal\block_render\Response\BlockResponseInterface');

    $normalizer = new BlockResponseNormalizer();

    $rendered = $this->getMockBuilder('\Throwable')
      ->getMock();

    $normalizer->normalize($rendered);
  }

}
