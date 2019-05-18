<?php

namespace Drupal\Tests\key_value_field\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * @coversDefaultClass \Drupal\key_value_field\Plugin\Field\FieldWidget\KeyValueTextfieldWidget
 * @group key_value_field
 */
class KeyValueTextfieldWidgetTest extends KernelTestBase {

  /**
   * Testing key_value_textfield widget setting.
   */
  public function testWidgetSettings() {
    $this->createTestField('key_value');

    $entity_form_display = EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $entity_form_display->setComponent('test_key_value_field', ['type' => 'key_value_textfield']);
    $entity_form_display->save();
  }

}
