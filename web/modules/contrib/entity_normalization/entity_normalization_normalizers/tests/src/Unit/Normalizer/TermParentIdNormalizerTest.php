<?php

namespace Drupal\Tests\entity_normalization_normalizers\Unit\Normalizer;

use Drupal\entity_normalization_normalizers\Normalizer\TermParentIdNormalizer;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_normalization_normalizers\Normalizer\TermParentIdNormalizer
 * @group entity_normalization
 */
class TermParentIdNormalizerTest extends UnitTestCase {

  /**
   * The normalizer to test.
   *
   * @var \Drupal\entity_normalization_normalizers\Normalizer\TermParentIdNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->normalizer = new TermParentIdNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $term = $this->createMock(TermInterface::class);
    $this->assertTrue($this->normalizer->supportsNormalization($term));

    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    $this->assertFalse($this->normalizer->supportsNormalization([]));
    $this->assertFalse($this->normalizer->supportsNormalization(NULL));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $term = $this->createMock(TermInterface::class);

    $term->parents = [];
    $this->assertEquals(0, $this->normalizer->normalize($term));

    $term->parents = [2];
    $this->assertEquals(2, $this->normalizer->normalize($term));

    $term->parents = [4, 3];
    $this->assertEquals(4, $this->normalizer->normalize($term));
  }

}
