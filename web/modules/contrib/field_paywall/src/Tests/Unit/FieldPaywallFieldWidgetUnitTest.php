<?php

/**
 * @file
 * Contains \Drupal\field_paywall\Tests\Unit\FieldPaywallFieldItemUnitTest.
 */

namespace Drupal\field_paywall\Tests\Unit;

use Drupal\Core\Form\FormState;

/**
 * @coversDefaultClass \Drupal\field_paywall\Plugin\Field\FieldType\PaywallWidget
 * @group Paywall
 */
class FieldPaywallFieldWidgetUnitTest extends FieldPaywallUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('field_paywall');

  /**
   * @covers ::formElement
   */
  public function testFormElement() {
    $entity = $this->createTestEntity(TRUE);
    $paywallWidget = $this->getFieldWidgetFromEntity($entity);

    $items = $entity->get('field_paywall');
    $delta = 0;
    $element = array();
    $form = array();
    $form_state = new FormState();
    $form_element_output = $paywallWidget->formElement($items, $delta, $element, $form, $form_state);

    $entity->field_paywall[0]->setValue(array(
      'enabled' => 0,
    ));
    $entity->save();
    $disabled_items = $entity->get('field_paywall');
    $form_element_output_disabled = $paywallWidget->formElement($disabled_items, $delta, $element, $form, $form_state);

    $this->assertTrue(!empty($form_element_output['enabled']), 'Enabled form element found');
    $this->assertEqual('Enabled', $form_element_output['enabled']['#title'], 'Enabled form element title correct');
    $this->assertEqual('checkbox', $form_element_output['enabled']['#type'], 'Enabled form element type correct');

    // Test both scenarios in which the default value is checked and not checked.
    $this->assertEqual(1, $form_element_output['enabled']['#default_value'], 'Enabled form element default value correct');
    $this->assertEqual(0, $form_element_output_disabled['enabled']['#default_value'], 'Disabled form element default value correct');
  }
}