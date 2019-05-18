<?php

namespace Drupal\Tests\entity_reference_text\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\entity_reference_text\Kernel\EntityReferenceTextBase;

/**
 * @coversDefaultClass \Drupal\entity_reference_text\Plugin\Field\FieldWidget\EntityReferenceWithTextAutocompletion
 * @group entity_reference_text
 */
class EntityReferenceWithTextAutocompletionTest extends EntityReferenceTextBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    EntityTest::create([
      'name' => 'bernd',
      'bundle' => 'entity_test',
    ])->save();
    EntityTest::create([
      'name' => 'hans',
      'bundle' => 'entity_test',
    ])->save();
    EntityTest::create([
      'name' => 'Actual Name',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * @dataProvider providerTestMassageFormValues
   * @covers ::massageFormValues
   */
  public function testMassageFormValues($expected, $values) {
    $form_state = new FormState();
    $result = $this->getTestWidget()->massageFormValues($values, [], $form_state);
    $this->assertEquals($expected, $result);
  }

  /**
   * Returns the tested widget.
   *
   * @return \Drupal\entity_reference_text\Plugin\Field\FieldWidget\EntityReferenceWithTextAutocompletion
   */
  protected function getTestWidget() {
    /** @var \Drupal\Core\Field\WidgetPluginManager $widget_manager */
    $widget_manager = \Drupal::service('plugin.manager.field.widget');
    /** @var \Drupal\entity_reference_text\Plugin\Field\FieldWidget\EntityReferenceWithTextAutocompletion $widget */

    $widget = $widget_manager->createInstance('entity_reference_text_autocompletion', ['field_definition' => $this->field, 'settings' => [], 'third_party_settings' => []]);
    return $widget;
  }


  public function providerTestMassageFormValues() {
    $data = [];
    $data['empty-data'] = [
      [],
      [],
    ];

    $data['value-field-format'] = [
      [['value' => 'hello (1) (2) (3) world']],
      [['value' => 'hello (1) (2) (3) world']],
    ];

    $data['value-widget-format'] = [
      [['value' => 'hello (1) and (2) and (3) world']],
      [['value' => 'hello @bernd and @hans and @Actual_Name world']],
    ];

    return $data;
  }

  /**
   * @dataProvider providerTestConvertStoredToInputValue
   * @covers ::convertStoredToInputValue
   */
  public function testConvertStoredToInputValue($expected, $value) {
    $entity = EntityTest::create([
      'name' => 'test',
      'bundle' => 'entity_test',
      'field_test' => $value,
    ]);

    $form_state = new FormState();
    $form = [];
    $element = $this->getTestWidget()->formElement($entity->get('field_test'), 0, [], $form, $form_state);
    $this->assertEquals($expected, $element['value']['#default_value']);
  }

  public function providerTestConvertStoredToInputValue() {
    $data = [];
    $data['empty-data'] = [
      '',
      [],
    ];

    $data['value-widget-format'] = [
      'hello @bernd and @hans and @Actual_Name world',
      'hello @bernd and @hans and @Actual_Name world',
    ];

    $data['value-field-format'] = [
      'hello @bernd and @hans and @Actual_Name world',
      'hello (1) and (2) and (3) world',
    ];

    return $data;
  }

}
