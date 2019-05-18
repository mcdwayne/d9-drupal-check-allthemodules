<?php

namespace Drupal\tests\select_or_other\Unit;

use Drupal\Core\Form\FormState;
use Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionMethod;

/**
 * Tests the form element implementation.
 *
 * @group select_or_other
 * @covers Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase
 */
class SelectOrOtherWidgetBaseTest extends UnitTestCase {

  protected static $testedClassName = 'Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase';

  /**
   * @var PHPUnit_Framework_MockObject_MockObject $stub
   */
  protected $widgetBaseMock;

  /**
   * @var PHPUnit_Framework_MockObject_MockObject $fieldDefinition
   */
  protected $fieldDefinition;

  /**
   * @var PHPUnit_Framework_MockObject_MockObject $containerMock
   */
  protected $containerMock;

  protected function setUp() {
    parent::setUp();
    $container_class = 'Drupal\Core\DependencyInjection\Container';
    $methods = get_class_methods($container_class);
    $this->containerMock = $this->getMockBuilder($container_class)
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
    \Drupal::setContainer($this->containerMock);

    $this->fieldDefinition = $this->getMockForAbstractClass('\Drupal\Core\Field\FieldDefinitionInterface');
    $arguments = [
      '',
      '',
      $this->fieldDefinition,
      [],
      [],
    ];

    $this->widgetBaseMock = $this->getMockForAbstractClass($this::$testedClassName, $arguments);
    /** @var SelectOrOtherWidgetBase $mock */
    $mock = $this->widgetBaseMock;
    $mock->setStringTranslation($this->getStringTranslationStub());
    $mock->setSettings([]);

  }

  /**
   * Test if defaultSettings() returns the correct keys.
   */
  public function testDefaultSettings() {
    $expected_keys = [
      'select_element_type',
      'available_options',
      'other',
      'other_title',
      'other_unknown_defaults',
      'other_size',
      'sort_options',
    ];

    $actual_keys = array_keys(SelectOrOtherWidgetBase::defaultSettings());
    $this->assertArrayEquals($expected_keys, $actual_keys);
  }

  /**
   * Tests functionality of SelectOrOtherWidgetBase::settingsForm
   */
  public function testSettingsForm() {
    $dummy_form = [];
    $dummy_state = new FormState();
    $expected_keys = [
      '#title',
      '#type',
      '#options',
      '#default_value',
    ];

    $element_key = 'select_element_type';
    $options = ['select_or_other_select', 'select_or_other_buttons'];
    /** @var SelectOrOtherWidgetBase $mock */
    $mock = $this->widgetBaseMock;
    foreach ($options as $option) {
      $mock->setSetting($element_key, $option);
      $form = $mock->settingsForm($dummy_form, $dummy_state);
      $this->assertArrayEquals($expected_keys, array_keys($form[$element_key]), 'Settings form has the expected keys');
      $this->assertArrayEquals($options, array_keys($form[$element_key]['#options']), 'Settings form has the expected options.');
      $this->assertEquals($option, $form[$element_key]['#default_value'], 'default value is correct.');
    }
  }

  /**
   * Tests the functionality of SelectOrOtherWidgetBase::settingsSummary
   */
  public function testSettingsSummary() {
    /** @var SelectOrOtherWidgetBase $mock */
    $mock = $this->widgetBaseMock;
    $elementTypeOptions = new ReflectionMethod($this::$testedClassName, 'selectElementTypeOptions');
    $elementTypeOptions->setAccessible(TRUE);
    $options = $elementTypeOptions->invoke($mock);
    foreach ($options as $option => $label) {
      $mock->setSetting('select_element_type', $option);

      $expected = ['Type of select form element: ' . $label];
      $summary = $mock->settingsSummary();

      $this->assertArrayEquals($expected, $summary);
    }
  }

  /**
   * Tests the functionality of several small helper methods.
   */
  public function testHelperMethods() {
    $storageStub = $this->getMockForAbstractClass('\Drupal\Core\Field\FieldStorageDefinitionInterface');
    $storageStub->expects($this->exactly(2))
      ->method('isMultiple')->will($this->onConsecutiveCalls(TRUE, FALSE));
    $this->fieldDefinition->expects($this->exactly(2))
      ->method('getFieldStorageDefinition')
      ->willReturn($storageStub);
    $this->fieldDefinition->expects($this->exactly(2))
      ->method('isRequired')
      ->will($this->onConsecutiveCalls(TRUE, FALSE));

    $isMultiple = new ReflectionMethod($this::$testedClassName, 'isMultiple');
    $isMultiple->setAccessible(TRUE);
    $this->assertTrue($isMultiple->invoke($this->widgetBaseMock));
    $this->assertFalse($isMultiple->invoke($this->widgetBaseMock));

    $isRequired = new ReflectionMethod($this::$testedClassName, 'isRequired');
    $isRequired->setAccessible(TRUE);
    $this->assertTrue($isRequired->invoke($this->widgetBaseMock));
    $this->assertFalse($isRequired->invoke($this->widgetBaseMock));


  }


  /**
   * Tests the functionality of SelectOrOtherWidgetBase::getSelectedOptions.
   */
  public
  function testGetSelectedOptions() {
    // Mock the widget
    $mock = $this->getMockBuilder('Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase')
      ->disableOriginalConstructor()
      ->setMethods(['getColumn', 'getOptions'])
      ->getMockForAbstractClass();
    $mock->expects($this->any())->method('getColumn')->willReturn('id');
    $mock->expects($this->any())
      ->method('getOptions')
      ->willReturnOnConsecutiveCalls([], [1 => 1, 2 => 2, 3 => 3]);

    // Mock up some entities.
    $entity1 = $this->getMockForAbstractClass('Drupal\Core\Entity\EntityInterface');
    $entity1->id = 1;
    $entity2 = $this->getMockForAbstractClass('Drupal\Core\Entity\EntityInterface');
    $entity2->id = 3;

    // Put the entities in a mocked list.
    $items = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->any())
      ->method('valid')
      ->willReturnOnConsecutiveCalls(TRUE, TRUE, FALSE, TRUE, TRUE, FALSE);
    $items->expects($this->any())
      ->method('current')
      ->willReturnOnConsecutiveCalls($entity1, $entity2, $entity1, $entity2);

    // Make getSelectedOptions accessible.
    $getSelectedOptionsMethod = new ReflectionMethod($this::$testedClassName, 'getSelectedOptions');
    $getSelectedOptionsMethod->setAccessible(TRUE);

    $expected = [];
    $selected_options = $getSelectedOptionsMethod->invokeArgs($mock, [$items]);
    $this->assertArrayEquals($expected, $selected_options, 'Selected options without a matching option are filtered out.');

    /** @var SelectOrOtherWidgetBase $mock */
    $expected = [1, 3];
    $selected_options = $getSelectedOptionsMethod->invokeArgs($mock, [$items]);
    $this->assertArrayEquals($expected, $selected_options, 'Selected options with matching options are kept.');
  }

}
