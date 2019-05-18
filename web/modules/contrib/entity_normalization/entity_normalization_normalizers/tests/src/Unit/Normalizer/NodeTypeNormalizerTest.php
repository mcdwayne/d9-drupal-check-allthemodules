<?php

namespace Drupal\Tests\entity_normalization_normalizers\Unit\Normalizer;

use Drupal\entity_normalization_normalizers\Normalizer\NodeTypeNormalizer;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_normalization_normalizers\Normalizer\NodeTypeNormalizer
 * @group entity_normalization
 */
class NodeTypeNormalizerTest extends UnitTestCase {

  /**
   * The normalizer to test.
   *
   * @var \Drupal\entity_normalization_normalizers\Normalizer\NodeTypeNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->normalizer = new NodeTypeNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $nodeType = $this->createMock(NodeTypeInterface::class);
    $this->assertTrue($this->normalizer->supportsNormalization($nodeType));

    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    $this->assertFalse($this->normalizer->supportsNormalization([]));
    $this->assertFalse($this->normalizer->supportsNormalization(NULL));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $nodeType = $this->createMock(NodeTypeInterface::class);
    $nodeType->expects($this->once())
      ->method('id')
      ->willReturn('article');
    $this->assertEquals('article', $this->normalizer->normalize($nodeType));
  }

}
