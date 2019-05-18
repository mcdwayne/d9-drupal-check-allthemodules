<?php

namespace Drupal\Tests\entity_reference_text\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\user\Entity\User;

/**
 * Tests the 'entity_reference_without_text' formatter.
 *
 * @coversDefaultClass \Drupal\entity_reference_text\Plugin\Field\FieldFormatter\EntityReferenceWithoutText
 *
 * @group entity_reference_text
 */
class EntityReferenceWithoutTextTest extends EntityReferenceTextBase {

  /**
   * @covers ::viewElements
   * @covers ::doViewElement
   */
  public function testFormatter() {
    $user = User::create([
      'name' => 'anonymous',
      'status' => TRUE,
    ]);
    $user->save();
    // Create a bunch of test entities.
    $test_entities = [];
    for ($i = 0; $i < 3; $i++) {
      $entity = EntityTest::create([
        'user_id' => ['target_id' => $user->id()],
        'name' => 'name_' . $i,
      ]);
      $entity->save();
      $test_entities[$entity->id()] = $entity;
    }

    $entity = EntityTest::create([
      'field_test' => [
        'value' => 'hello world (1) (2) (3)',
        'entity_ids' => [1, 2, 3],
      ]
    ]);
    $entity->save();

    $result = $entity->get('field_test')->view([
      'type' => 'entity_reference_without_text',
    ]);

    $rendered = $this->render($result);
    $this->setRawContent($rendered);
    $this->assertLink('name_0');
    $this->assertLink('name_1');
    $this->assertLink('name_1');

    $result = $entity->get('field_test')->view([
      'type' => 'entity_reference_without_text',
      'settings' => [
        'link' => FALSE,
      ],
    ]);
    $rendered = $this->render($result);
    $this->setRawContent($rendered);
    $this->assertNoLink('name_0');
    $this->assertNoLink('name_1');
    $this->assertNoLink('name_2');
    $this->assertText('name_0');
    $this->assertText('name_1');
    $this->assertText('name_2');

    // Test the limit support.
    $result = $entity->get('field_test')->view([
      'type' => 'entity_reference_without_text',
      'settings' => [
        'link' => FALSE,
        'limit' => 2,
      ],
    ]);
    $rendered = $this->render($result);
    $this->setRawContent($rendered);
    $this->assertNoLink('name_0');
    $this->assertNoLink('name_1');
    $this->assertNoLink('name_2');
    $this->assertText('name_0');
    $this->assertText('name_1');
    $this->assertNoText('name_2');
  }

}
