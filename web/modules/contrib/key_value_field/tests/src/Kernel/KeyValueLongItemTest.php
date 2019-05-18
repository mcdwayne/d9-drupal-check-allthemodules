<?php

namespace Drupal\Tests\key_value_field\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\filter\Entity\FilterFormat;

/**
 * @coversDefaultClass \Drupal\key_value_field\Plugin\Field\FieldType\KeyValueLongItem
 * @group key_value_field
 */
class KeyValueLongItemTest extends KernelTestBase {

  /**
   * Test key_value_long field creation.
   */
  public function testFieldCreate() {
    $this->createTestField('key_value_long');

    $entity = EntityTest::create([
      'test_key_value_field' => ['value' => ''],
    ]);
    $entity->save();

    $this->assertTrue($entity->test_key_value_field->isEmpty());
    $this->assertEquals(NULL, $entity->test_key_value_field->value);
  }

  /**
   * Test field creation with default values.
   */
  public function testFieldCreateWithDefaultValue() {
    $field_settings = [
      'default_value' => [
        0 => [
          'value' => 'orange',
          'key' => 'apple',
        ],
      ],
    ];
    $this->createTestField('key_value_long', [], $field_settings);

    $entity = EntityTest::create([]);
    $entity->save();

    $this->assertFalse($entity->test_key_value_field->isEmpty());
    $this->assertEquals('apple', $entity->test_key_value_field->key);
    $this->assertEquals('orange', $entity->test_key_value_field->value);
    $this->assertEquals(NULL, $entity->test_key_value_field->description);
  }

  /**
   * Test storing data to field.
   */
  public function testWithData() {
    $this->createTestField('key_value_long');

    $entity = EntityTest::create([
      'test_key_value_field' => ['value' => 'orange', 'key' => 'apple'],
    ]);
    $entity->save();

    $this->assertFalse($entity->test_key_value_field->isEmpty());
    $this->assertEquals('apple', $entity->test_key_value_field->key);
    $this->assertEquals('orange', $entity->test_key_value_field->value);
    $this->assertEquals(NULL, $entity->test_key_value_field->description);
    // Unless specified we fallback to the default, see
    // \Drupal\text\Plugin\Field\FieldType\TextItemBase::applyDefaultValue.
    $this->assertEquals(NULL, $entity->test_key_value_field->format);
  }

  /**
   * Test storing data to field with different format.
   */
  public function testWithDifferentDefaultFormat() {
    $format = FilterFormat::load('plain_text')->createDuplicate()->set('format', 'muh');
    $format->save();

    $this->createTestField('key_value_long', [], ['default_format' => 'muh']);

    $entity = EntityTest::create([
      'test_key_value_field' => ['value' => 'orange', 'key' => 'apple'],
    ]);
    $entity->save();

    $this->markTestSkipped('You cannot yet configure a different default format???');
    $this->assertEquals('muh', $entity->test_key_value_field->format);
  }

  /**
   * Test storing data to field with description.
   */
  public function testWithDataAndDescription() {
    $this->createTestField('key_value_long');

    $entity = EntityTest::create([
      'test_key_value_field' => [
        'value' => 'orange',
        'key' => 'apple',
        'description' => 'some description text',
      ],
    ]);
    $entity->save();

    $this->assertFalse($entity->test_key_value_field->isEmpty());
    $this->assertEquals('apple', $entity->test_key_value_field->key);
    $this->assertEquals('orange', $entity->test_key_value_field->value);
    $this->assertEquals('some description text', $entity->test_key_value_field->description);
  }

  /**
   * Test storing data to field with maximum key length constraint.
   *
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   */
  public function testMaximumKeyLength() {
    $this->createTestField('key_value_long', ['settings' => ['key_max_length' => 10]]);

    // @todo I would have expected some validation.
    $entity = EntityTest::create([
      'test_key_value_field' => ['value' => 'orange', 'key' => 'this-is-really-a-long-key'],
    ]);
    $entity->save();
  }

}
