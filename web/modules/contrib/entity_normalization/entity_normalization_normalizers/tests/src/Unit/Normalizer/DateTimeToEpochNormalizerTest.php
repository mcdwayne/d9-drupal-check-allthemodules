<?php

namespace Drupal\Tests\entity_normalization_normalizers\Unit\Normalizer;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity_normalization_normalizers\Normalizer\DateTimeToEpochNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_normalization_normalizers\Normalizer\DateTimeToEpochNormalizer
 * @group entity_normalization
 */
class DateTimeToEpochNormalizerTest extends UnitTestCase {

  /**
   * The normalizer to test.
   *
   * @var \Drupal\entity_normalization_normalizers\Normalizer\DateTimeToEpochNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->normalizer = new DateTimeToEpochNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $datetime = $this->createMock(DateTimeItem::class);

    $this->assertTrue($this->normalizer->supportsNormalization($datetime));

    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    $this->assertFalse($this->normalizer->supportsNormalization([]));
    $this->assertFalse($this->normalizer->supportsNormalization(NULL));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $dateItem = $this->createMock(DateTimeItem::class);
    $dateItem->expects($this->once())
      ->method('getValue')
      ->willReturn(['value' => '2018-01-01']);

    $this->assertSame(strtotime('2018-01-01'), $this->normalizer->normalize($dateItem));
  }

}
