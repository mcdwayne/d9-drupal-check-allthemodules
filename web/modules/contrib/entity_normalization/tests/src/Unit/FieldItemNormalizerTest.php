<?php

namespace Drupal\Tests\entity_normalization\Unit\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_normalization\FieldConfigInterface;
use Drupal\entity_normalization\Normalizer\FieldItemNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_normalization\Normalizer\FieldItemNormalizer
 * @group entity_normalization
 */
class FieldItemNormalizerTest extends UnitTestCase {

  /**
   * The normalizer to test.
   *
   * @var \Drupal\entity_normalization\Normalizer\FieldItemNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->normalizer = new FieldItemNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testUnSupportedNormalization() {
    $this->assertFalse($this->normalizer->supportsNormalization(NULL));
    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    $this->assertFalse($this->normalizer->supportsNormalization([]));

    $this->assertFalse($this->normalizer->supportsNormalization(
      $this->prophesize(FieldItemInterface::class)->reveal()
    ));
    $this->assertFalse($this->normalizer->supportsNormalization(NULL, NULL, [
      'field_config' => $this->prophesize(FieldConfigInterface::class)->reveal(),
    ]));
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $this->assertTrue($this->normalizer->supportsNormalization(
      $this->prophesize(FieldItemInterface::class)->reveal(),
      NULL,
      ['field_config' => $this->prophesize(FieldConfigInterface::class)->reveal()]
    ));
  }

}
