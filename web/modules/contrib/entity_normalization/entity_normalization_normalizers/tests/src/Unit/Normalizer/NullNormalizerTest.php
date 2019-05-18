<?php

namespace Drupal\Tests\entity_normalization_normalizers\Unit\Normalizer;

use Drupal\entity_normalization_normalizers\Normalizer\NullNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_normalization_normalizers\Normalizer\NullNormalizer
 * @group entity_normalization
 */
class NullNormalizerTest extends UnitTestCase {

  /**
   * The normalizer to test.
   *
   * @var \Drupal\entity_normalization_normalizers\Normalizer\NullNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->normalizer = new NullNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $this->assertTrue($this->normalizer->supportsNormalization(NULL));
    $this->assertTrue($this->normalizer->supportsNormalization(new \stdClass()));
    $this->assertTrue($this->normalizer->supportsNormalization([]));

    $this->assertTrue($this->normalizer->supportsNormalization(NULL, 'randomFormat'));
    $this->assertTrue($this->normalizer->supportsNormalization(new \stdClass(), 'randomFormat'));
    $this->assertTrue($this->normalizer->supportsNormalization([], 'randomFormat'));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $this->assertNull($this->normalizer->normalize(NULL));
    $this->assertNull($this->normalizer->normalize(new \stdClass()));
    $this->assertNull($this->normalizer->normalize([]));

    $this->assertNull($this->normalizer->normalize(NULL, 'randomFormat'));
    $this->assertNull($this->normalizer->normalize(new \stdClass(), 'randomFormat'));
    $this->assertNull($this->normalizer->normalize([], 'randomFormat'));
  }

}
