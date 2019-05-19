<?php

namespace Drupal\Tests\yamlencoder\Kernel;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Serialization\Yaml;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\serialization\Kernel\NormalizerTestBase;

/**
 * Tests that entities can be serialized to/from YAML.
 *
 * @group yamlencoder
 */
class YamlEncoderTest extends NormalizerTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['yamlencoder', 'serialization', 'system', 'field', 'entity_test', 'text', 'filter'];

  /**
   * The test values.
   *
   * @var array
   */
  protected $values;

  /**
   * The test entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer.
   */
  protected $serializer;

  /**
   * The class name of the test class.
   *
   * @var string
   */
  protected $entityClass = EntityTestMulRev::class;

  protected function setUp() {
    parent::setUp();

    FilterFormat::create([
      'format' => 'my_text_format',
      'name' => 'My Text Format',
    ])->save();

    // Create a test entity to serialize.
    $this->values = [
      'name' => 'test entity',
      'field_test_text' => [
        'value' => 'Test text content',
        'format' => 'my_text_format',
      ],
    ];
    $this->entity = EntityTestMulRev::create($this->values);
    $this->entity->save();

    $this->serializer = $this->container->get('serializer');

    $this->installConfig(['field']);
  }

  /**
   * Test entity serialization in YAML.
   */
  public function testSerialize() {
    $normalized = $this->serializer->normalize($this->entity, 'yaml');
    $expected = Yaml::encode($normalized);
    $actual_full = $this->serializer->serialize($this->entity, 'yaml');
    $actual_normalized = $this->serializer->serialize($normalized, 'yaml');

    $this->assertSame($actual_full, $expected, 'Entity serializes to YAML when "yaml" is requested.');
    $this->assertSame($actual_normalized, $expected, 'A normalized array serializes to YAML when "yaml" is requested');
  }

  /**
   * Test entity deserialization and denormalization with YAML.
   */
  public function testDeserialize() {
    $normalized = $this->serializer->normalize($this->entity, 'yaml');
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'yaml');
    $serialized = $this->serializer->serialize($this->entity, 'yaml');
    $deserialized = $this->serializer->deserialize($serialized, $this->entityClass, 'yaml');

    foreach ([$denormalized, $deserialized] as $actual) {
      $this->assertTrue($actual instanceof $this->entityClass, SafeMarkup::format('Entity is an instance of @class', ['@class' => $this->entityClass]));
      $this->assertSame($this->entity->getEntityTypeId(), $actual->getEntityTypeId(), 'Expected entity type found.');
      $this->assertSame($this->entity->bundle(), $actual->bundle(), 'Expected entity bundle found.');
      $this->assertSame($this->entity->uuid(), $actual->uuid(), 'Expected entity UUID found.');
      $this->assertSame($this->entity->name->value, $actual->name->value, 'Expected entity name found.');
      $this->assertSame($this->entity->field_test_text->value, $actual->field_test_text->value, 'Expected text field value found.');
      $this->assertSame($this->entity->field_test_text->format, $actual->field_test_text->format, 'Expected text field format found.');
    }
  }

}
