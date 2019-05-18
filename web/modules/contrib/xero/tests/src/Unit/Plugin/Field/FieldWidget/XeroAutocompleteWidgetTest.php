<?php

namespace Drupal\Tests\xero\Unit\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormState;

/**
 * Provides a base test class for testing field widgets.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\Field\FieldWidget\XeroAutocompleteWidget
 * @group Xero
 */
class XeroAutocompleteWidgetTest extends WidgetTestBase {

  /**
   * The plugin id of the widget.
   *
   * @return string
   *   The plugin id of the widget.
   */
  protected function getPluginId() {
    return 'xero_textfield';
  }

  /**
   * The plugin class of the widget.
   *
   * @return string
   *   The plugin class of the widget.
   */
  protected function getPluginClass() {
    return '\Drupal\xero\Plugin\Field\FieldWidget\XeroAutocompleteWidget';
  }

  /**
   * Assert that the settings-related methods function.
   */
  public function testSettings() {

    $formState = new FormState();

    $settings = $this->widget->settingsSummary();
    $settingsForm = $this->widget->settingsForm(array(), $formState);
    $this->assertEquals('Xero type: Not set', $settings[0]);
    $this->assertEmpty($settingsForm['xero_type']['#default_value']);

    $this->widget->setSetting('xero_type', 'xero_employee');
    $settings = $this->widget->settingsSummary();
    $settingsForm = $this->widget->settingsForm([], $formState);
    $this->assertEquals('Xero type: Xero Employee', $settings[0]);
    $this->assertEquals('xero_employee', $settingsForm['xero_type']['#default_value']);

    $this->widget->setSetting('xero_type', 'xero_garbage');
    $settings = $this->widget->settingsSummary();
    $this->assertEquals('Xero type: Not set', $settings[0]);
  }

  /**
   * Assert that the formElement works.
   *
   * @dataProvider formValueProvider
   */
  public function testFormElement($values, $type, $expected) {
    $form = [];
    $element = ['#required' => FALSE, '#delta' => [0], '#parents' => ['field_fake', 0]];
    $formState = new FormState();
    $this->widget->setSetting('xero_type', $type);

    $element = $this->widget->formElement($this->fieldItemList, 0, $element, $form, $formState);

    $this->assertEquals('textfield', $element['#type']);
    $this->assertEquals(['type' => $type], $element['#autocomplete_route_parameters']);

    $this->assertEquals($expected, $this->widget->massageFormValues($values, $form, $formState));
  }

  /**
   * Provide form values and the expected output for massageFormValues.
   *
   * @return array
   *   An array of parameters to use for the test.
   */
  public function formValueProvider() {
    $label = $this->getRandomGenerator()->word(10);
    $guid = $this->createGuid(FALSE);

    return [
      [[], 'xero_employee', []],
      [[$guid], 'xero_employee', [0 => ['type' => 'xero_employee', 'label' => '', 'guid' => $guid]]],
      [[$guid . ' (' . $label . ')'], 'xero_employee', [0 => ['type' => 'xero_employee', 'label' => $label, 'guid' => $guid]]]
    ];
  }

}
