<?php

namespace Drupal\Tests\entity_normalization_normalizers\Unit\Normalizer;

use Drupal\entity_normalization_normalizers\Normalizer\RichTextNormalizer;
use Drupal\Tests\UnitTestCase;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;
use Drupal\text\Plugin\Field\FieldType\TextLongItem;
use Drupal\text\Plugin\Field\FieldType\TextWithSummaryItem;

/**
 * @coversDefaultClass \Drupal\entity_normalization_normalizers\Normalizer\RichTextNormalizer
 * @group entity_normalization
 */
class RichTextNormalizerTest extends UnitTestCase {

  /**
   * The normalizer to test.
   *
   * @var \Drupal\entity_normalization_normalizers\Normalizer\RichTextNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->normalizer = new RichTextNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $textLongItem = $this->createMock(TextLongItem::class);
    $this->assertTrue($this->normalizer->supportsNormalization($textLongItem));

    $textWithSummaryItem = $this->createMock(TextWithSummaryItem::class);
    $this->assertTrue($this->normalizer->supportsNormalization($textWithSummaryItem));

    $textItemBase = $this->createMock(TextItemBase::class);
    $this->assertFalse($this->normalizer->supportsNormalization($textItemBase));

    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    $this->assertFalse($this->normalizer->supportsNormalization([]));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $textWithSummaryItem = $this->createMock(TextWithSummaryItem::class);
    $textWithSummaryItem->expects($this->once())
      ->method('getValue')
      ->willReturn(['value' => 'test']);
    $this->assertEquals('test', $this->normalizer->normalize($textWithSummaryItem));
  }

}
