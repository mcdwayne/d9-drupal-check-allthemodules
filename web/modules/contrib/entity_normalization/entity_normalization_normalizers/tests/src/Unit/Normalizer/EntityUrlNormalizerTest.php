<?php

namespace Drupal\Tests\entity_normalization_normalizers\Unit\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Url;
use Drupal\entity_normalization_normalizers\Normalizer\EntityUrlNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_normalization_normalizers\Normalizer\EntityUrlNormalizer
 * @group entity_normalization
 */
class EntityUrlNormalizerTest extends UnitTestCase {

  /**
   * The normalizer to test.
   *
   * @var \Drupal\entity_normalization_normalizers\Normalizer\EntityUrlNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->normalizer = new EntityUrlNormalizer();
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $entity = $this->createMock(EntityInterface::class);
    $this->assertTrue($this->normalizer->supportsNormalization($entity));

    $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    $this->assertFalse($this->normalizer->supportsNormalization([]));
    $this->assertFalse($this->normalizer->supportsNormalization(NULL));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $testUrl = '/test/1234';

    $generatedUrl = $this->prophesize(GeneratedUrl::class);
    $generatedUrl->getGeneratedUrl()->willReturn($testUrl)->shouldBeCalled();

    $url = $this->prophesize(Url::class);
    $url->toString()->withArguments([TRUE])->willReturn($generatedUrl)->shouldBeCalled();

    $entity = $this->prophesize(EntityInterface::class);
    $entity->toUrl()->willReturn($url)->shouldBeCalled();

    $normalized = $this->normalizer->normalize($entity->reveal());
    $this->assertEquals($testUrl, $normalized);
  }

}
