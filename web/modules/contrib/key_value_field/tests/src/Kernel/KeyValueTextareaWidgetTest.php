<?php

namespace Drupal\Tests\key_value_field\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * @coversDefaultClass \Drupal\key_value_field\Plugin\Field\FieldWidget\KeyValueTextareaWidget
 * @group key_value_field
 */
class KeyValueTextareaWidgetTest extends KernelTestBase {

  /**
   * Testing key_value_textarea widget settings.
   */
  public function testWidgetSettings() {
    $this->createTestField('key_value_long');

    $entity_form_display = EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $entity_form_display->setComponent('test_key_value_field', ['type' => 'key_value_textarea']);
    $entity_form_display->save();
  }

}
