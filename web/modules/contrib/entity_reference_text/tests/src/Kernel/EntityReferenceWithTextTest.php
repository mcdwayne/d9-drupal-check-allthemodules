<?php

namespace Drupal\Tests\entity_reference_text\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\user\Entity\User;

/**
 * @group entity_reference_text
 */
class EntityReferenceWithTextTest extends EntityReferenceTextBase {

  public function testStorage() {
    $entity = EntityTest::create([
      'field_test' => [
        'value' => '',
      ]
    ]);
    $entity->save();
    $entity = EntityTest::load($entity->id());
    $this->assertTrue($entity->field_test->isEmpty());

    $entity->field_test->value = 'hello world';
    $entity->save();

    $this->assertFalse($entity->field_test->isEmpty());
    $this->assertEquals('hello world', $entity->field_test->value);
    $this->assertEquals([], $entity->field_test->entity_ids);

    $entity->field_test->value = 'hello world (1) (2) (3)';
    $entity->save();
    $entity = EntityTest::load($entity->id());

    $this->assertEquals('hello world (1) (2) (3)', $entity->field_test->value);
    $this->assertEquals([1, 2, 3], $entity->field_test->entity_ids);

    $entity->field_test->value = 'hello (2) bernd (4) (3)';
    $entity->save();
    $entity = EntityTest::load($entity->id());

    $this->assertEquals('hello (2) bernd (4) (3)', $entity->field_test->value);
    $this->assertEquals([2, 4, 3], $entity->field_test->entity_ids);

    $entity->field_test->value = 'hello (test) bernd (test2) (test4)';
    $entity->save();
    $entity = EntityTest::load($entity->id());

    $this->assertEquals('hello (test) bernd (test2) (test4)', $entity->field_test->value);
    $this->assertEquals(['test', 'test2', 'test4'], $entity->field_test->entity_ids);
  }

  /**
   * @covers \Drupal\entity_reference_text\Plugin\Field\FieldType\EntityReferenceWithText::setValue
   * @covers \Drupal\entity_reference_text\Plugin\Field\FieldType\EntityReferenceWithText::onChange
   */
  public function testSetValue() {
    $entity = EntityTest::create([
      'field_test' => [
        'value' => '',
      ]
    ]);
    $entity->save();

    $entity->get('field_test')->value = 'hello (1) test';
    $this->assertEquals([1], $entity->get('field_test')->entity_ids);

    $entity = EntityTest::create([
      'field_test' => 'hello (1) test',
    ]);
    $this->assertEquals('hello (1) test', $entity->get('field_test')->value);
    $this->assertEquals([1], $entity->get('field_test')->entity_ids);
  }

  public function testValidation() {
    // Create an anonymous user.
    $user = User::create([
      'name' => 'anonymous',
      'status' => TRUE,
    ]);
    $user->save();

    // Create a bunch of test entities.
    for ($i = 0; $i < 3; $i++) {
      $entity = EntityTest::create([
        'user_id' => ['target_id' => $user->id()],
        'name' => 'name_' . $i,
      ]);
      $entity->save();
    }

    $entity = EntityTest::create([
      'user_id' => ['target_id' => $user->id()],
      'field_test' => [
        'value' => 'hello world (1) (2) (3)',
        'entity_ids' => [1, 2, 3],
      ]
    ]);
    $result = $entity->validate();
    $this->assertCount(0, $result);

    // Now reference a non existing entity.
    $entity = EntityTest::create([
      'user_id' => ['target_id' => $user->id()],
      'field_test' => [
        'value' => 'hello world (4)',
        'entity_ids' => [4],
      ]
    ]);
    $result = $entity->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('field_test.0.0.target_id', $result->get(0)->getPropertyPath());
    $this->assertEquals('The referenced entity (<em class="placeholder">entity_test</em>: <em class="placeholder">4</em>) does not exist.', $result->get(0)->getMessage());
  }

  public function testFormatter() {
    // Create an anonymous user.
    $user = User::create([
      'name' => 'anonymous',
      'status' => TRUE,
    ]);
    $user->save();
    for ($i = 0; $i < 3; $i++) {
      $entity = EntityTest::create([
        'user_id' => ['target_id' => $user->id()],
        'name' => 'name_' . $i,
      ]);
      $entity->save();
    }

    $entity = EntityTest::create([
      'user_id' => ['target_id' => $user->id()],
      'field_test' => [
        'value' => 'hello world (1) (2) (3)',
        'entity_ids' => [1, 2, 3],
      ]
    ]);
    $entity->save();

    $this->assertEquals('hello world <a href="/entity_test/1" hreflang="en">name_0</a> <a href="/entity_test/2" hreflang="en">name_1</a> <a href="/entity_test/3" hreflang="en">name_2</a>', $entity->field_test->view()[0]['#markup']);
    $this->assertEquals(['user.permissions'], $entity->get('field_test')->view()[0]['#cache']['contexts']);
    $this->assertEquals(['entity_test:1', 'entity_test:2', 'entity_test:3'], $entity->get('field_test')->view()[0]['#cache']['tags']);
  }

}
