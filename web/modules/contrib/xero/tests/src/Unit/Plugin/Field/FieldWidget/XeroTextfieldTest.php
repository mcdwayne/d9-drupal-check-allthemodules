<?php

namespace Drupal\Tests\xero\Unit\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormState;
use Drupal\Component\Uuid\Uuid;

/**
 * Provides a base test class for testing field widgets.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\Field\FieldWidget\XeroTextfieldWidget
 * @group Xero
 */
class XeroTextfieldTest extends WidgetTestBase {

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
    return '\Drupal\xero\Plugin\Field\FieldWidget\XeroTextfieldWidget';
  }

  /**
   * Assert that the settings-related methods function.
   */
  public function testSettings() {

    $formState = new FormState();

    $settings = $this->widget->settingsSummary();
    $settingsForm = $this->widget->settingsForm(array(), $formState);
    $this->assertEmpty($settings);
    $this->assertEmpty($settingsForm['xero_type']['#default_value']);

    $this->widget->setSetting('xero_type', ['xero_employee' => 'xero_employee']);
    $settings = $this->widget->settingsSummary();
    $settingsForm = $this->widget->settingsForm([], $formState);
    $this->assertEquals('Xero types: Xero Employee', $settings[0]);
    $this->assertEquals(['xero_employee' => 'xero_employee'], $settingsForm['xero_type']['#default_value']);

    $this->widget->setSetting('xero_type', ['xero_garbage' => 'xero_garbage']);
    $settings = $this->widget->settingsSummary();
    $this->assertEmpty($settings);
  }

  /**
   * Assert that the formElement works.
   */
  public function testFormElement() {
    $form = [];
    $element = ['#required' => FALSE, '#delta' => [0]];
    $formState = new FormState();
    $expectedOptions = ['xero_contact' => 'Xero Contact', 'xero_employee' => 'Xero Employee'];
    $this->widget->setSetting('xero_type', ['xero_contact' => 'xero_contact', 'xero_employee' => 'xero_employee']);

    $element = $this->widget->formElement($this->fieldItemList, 0, $element, $form, $formState);

    $this->assertEquals($expectedOptions, $element['type']['#options']);
    $this->assertTrue(Uuid::isValid($element['guid']['#placeholder']));
  }

}
