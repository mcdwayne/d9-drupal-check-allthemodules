<?php

namespace Drupal\Tests\key_value_field\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;

/**
 * @coversDefaultClass \Drupal\key_value_field\Plugin\Field\FieldFormatter\KeyValueFormatter
 * @group key_value_field
 */
class KeyValueFormatterTest extends KernelTestBase {

  /**
   * Tests default formatter settings.
   */
  public function testFormatter() {
    $this->createTestField('key_value');

    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $entity_view_display->setComponent('test_key_value_field', ['type' => 'key_value']);
    $entity_view_display->save();

    $entity = EntityTest::create([
      'test_key_value_field' => ['value' => "orange", 'key' => 'apple'],
    ]);
    $entity->save();

    $build = $entity_view_display->build($entity);
    $output = $this->render($build);
    // AssertRaw needs content to be set.
    $this->setRawContent($output);

    $this->assertRaw("apple : <p>orange</p>\n");
  }

  /**
   * Tests value only formatter setting.
   */
  public function testValueOnlyFormatter() {
    $this->createTestField('key_value');

    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);

    $entity_view_display->setComponent('test_key_value_field', ['type' => 'key_value', 'settings' => ['value_only' => TRUE]]);
    $entity_view_display->save();

    $entity = EntityTest::create([
      'test_key_value_field' => ['value' => "orange", 'key' => 'apple'],
    ]);
    $entity->save();

    $build = $entity_view_display->build($entity);
    $output = $this->render($build);
    // AssertRaw needs content to be set.
    $this->setRawContent($output);
    $this->assertRaw("<div><p>orange</p>\n", 'Found', 'KeyV');
  }

}
