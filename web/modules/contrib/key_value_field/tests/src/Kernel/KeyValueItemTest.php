<?php

namespace Drupal\Tests\key_value_field\Kernel;

use Drupal\entity_test\Entity\EntityTest;

/**
 * @coversDefaultClass \Drupal\key_value_field\Plugin\Field\FieldType\KeyValueItem
 * @group key_value_field
 */
class KeyValueItemTest extends KernelTestBase {

  /**
   * Test key_value field creation.
   */
  public function testFieldCreate() {
    $this->createTestField('key_value');

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
    $this->createTestField('key_value', [], $field_settings);

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
    $this->createTestField('key_value');

    $entity = EntityTest::create([
      'test_key_value_field' => ['value' => 'orange', 'key' => 'apple'],
    ]);
    $entity->save();

    $this->assertFalse($entity->test_key_value_field->isEmpty());
    $this->assertEquals('apple', $entity->test_key_value_field->key);
    $this->assertEquals('orange', $entity->test_key_value_field->value);
    $this->assertEquals(NULL, $entity->test_key_value_field->description);
  }

  /**
   * Test storing data to field with description.
   */
  public function testWithDataAndDescription() {
    $this->createTestField('key_value');

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
    $this->createTestField('key_value', ['settings' => ['key_max_length' => 10]]);

    // @todo I would have expected some validation.
    $entity = EntityTest::create([
      'test_key_value_field' => ['value' => 'orange', 'key' => 'this-is-really-a-long-key'],
    ]);
    $entity->save();
  }

}
