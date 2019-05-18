<?php

namespace Drupal\Tests\select_or_other\Unit;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\select_or_other\Plugin\Field\FieldWidget\EntityReference\ReferenceWidget;
use Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit_Framework_MockObject_MockBuilder;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the form element implementation.
 *
 * @group select_or_other
 * @covers Drupal\select_or_other\Plugin\Field\FieldWidget\EntityReference\ReferenceWidget
 */
class ReferenceWidgetTest extends UnitTestCase {

  protected static $testedClassName = 'Drupal\select_or_other\Plugin\Field\FieldWidget\EntityReference\ReferenceWidget';

  /**
   * @var PHPUnit_Framework_MockObject_MockBuilder $stub
   */
  protected $mockBuilder;

  /**
   * @var PHPUnit_Framework_MockObject_MockObject $containerMock
   */
  protected $containerMock;

  protected function setUp() {
    parent::setUp();
    $container_class = 'Drupal\Core\DependencyInjection\Container';
    $methods = get_class_methods($container_class);
    /** @var ContainerInterface $container */
    $this->containerMock = $container = $this->getMockBuilder($container_class)
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
    \Drupal::setContainer($container);

    $this->mockBuilder = $this->getMockBuilder($this::$testedClassName);
  }

  /**
   * Test if defaultSettings() returns the correct keys.
   */
  public function testGetOptions() {
    $entityID = 1;
    $entityLabel = 'Label';
    $entityMock = $this->getMockBuilder('\Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $entityMock->expects($this->exactly(1))
      ->method('id')
      ->willReturn($entityID);
    $entityMock->expects($this->exactly(2))
      ->method('label')
      ->willReturn($entityLabel);

    $entityStorageMock = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityStorageInterface');
    $entityStorageMock->expects($this->exactly(2))
      ->method('loadByProperties')
      ->willReturnOnConsecutiveCalls([], [$entityMock]);

    $mock = $this->mockBuilder->disableOriginalConstructor()
      ->setMethods([
        'getEntityStorage',
        'getBundleKey',
        'getSelectionHandlerSetting'
      ])
      ->getMock();
    $mock->expects($this->exactly(2))
      ->method('getEntityStorage')
      ->willReturn($entityStorageMock);
    $mock->expects($this->exactly(2))
      ->method('getBundleKey')
      ->willReturn('bundle');
    $mock->expects($this->exactly(2))
      ->method('getSelectionHandlerSetting')
      ->willReturn('target_bundle');

    $getOptions = new ReflectionMethod($mock, 'getOptions');
    $getOptions->setAccessible(TRUE);

    // First invocation returns an empty array because there are no entities.
    $options = $getOptions->invoke($mock);
    $expected = [];
    $this->assertArrayEquals($options, $expected);

    // Second invocation returns a key=>value array because there is one entity.
    $options = $getOptions->invoke($mock);
    $expected = ["{$entityLabel} ({$entityID})" => $entityLabel];
    $this->assertArrayEquals($options, $expected);
  }

  protected function prepareFormElementMock($target_type = 'entity', $testedClassName = FALSE) {
    $methods = [
      'getColumn',
      'getOptions',
      'getSelectedOptions',
      'getFieldSetting',
      'getAutoCreateBundle'
    ];

    // Get the mockBuilder
    if ($testedClassName) {
      $builder = $this->getMockBuilder($testedClassName);
    }
    else {
      $builder = $this->mockBuilder;
    }

    // Configure the mockBuilder.
    $field_definition = $this->getMockForAbstractClass('\Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->any())
      ->method('getFieldStorageDefinition')
      ->willReturn($this->getMockForAbstractClass('Drupal\Core\Field\FieldStorageDefinitionInterface'));
    $constructor_arguments = ['', '', $field_definition, [], []];

    $builder->setConstructorArgs($constructor_arguments)->setMethods($methods);

    if ($testedClassName) {
      $class = new \ReflectionClass($testedClassName);
      $mock = $class->isAbstract() ? $builder->getMockForAbstractClass() : $builder->getMock();
    }
    else {
      $mock = $builder->getMock();
    }

    // Configure the mock.
    $mock->expects($this->any())->method('getColumn')->willReturn('column');
    $mock->expects($this->any())->method('getOptions')->willReturn([]);
    $mock->expects($this->any())->method('getSelectedOptions')->willReturn([]);
    $mock->expects($this->any())
      ->method('getFieldSetting')
      ->willReturnOnConsecutiveCalls($target_type, 'some_handler', [], $target_type);
    $mock->expects($this->any())
      ->method('getAutoCreateBundle')
      ->willReturn('autoCreateBundle');

    return $mock;
  }

  /**
   * Test if formElement() adds the expected information.
   */
  public function testFormElement() {
    $userMock = $this->getMock('User', ['id']);
    $userMock->expects($this->any())->method('id')->willReturn(1);
    $this->containerMock->expects($this->any())
      ->method('get')
      ->with('current_user')
      ->willReturn($userMock);
    foreach (['node', 'taxonomy_term'] as $target_type) {
      /** @var ReferenceWidget $mock */
      $mock = $this->prepareFormElementMock($target_type);
      /** @var SelectOrOtherWidgetBase $parent */
      $parent = $this->prepareFormElementMock($target_type, 'Drupal\select_or_other\Plugin\Field\FieldWidget\SelectOrOtherWidgetBase');

      $entity_class = $target_type === 'taxonomy_term' ? 'Drupal\Core\Entity\EntityInterface' : 'Drupal\user\EntityOwnerInterface';
      $entity = $this->getMockForAbstractClass($entity_class);
      $entity->expects($this->any())->method('getOwnerId')->willReturn(1);
      $items = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemListInterface');
      $items->expects($this->any())->method('getEntity')->willReturn($entity);
      /** @var FieldItemListInterface $items */
      $delta = 1;
      $element = [];
      $form = [];
      $form_state = new FormState();

      $parentResult = $parent->formElement($items, $delta, $element, $form, $form_state);
      $result = $mock->formElement($items, $delta, $element, $form, $form_state);
      $added = array_diff_key($result, $parentResult);

      $expected = [
        '#target_type' => $target_type,
        '#selection_handler' => 'some_handler',
        '#selection_settings' => [],
        '#autocreate' => [
          'bundle' => 'autoCreateBundle',
          'uid' => 1,
        ],
        '#validate_reference' => TRUE,
        '#tags' => $target_type === 'taxonomy_term',
        '#merged_values' => TRUE,
      ];
      $this->assertArrayEquals($expected, $added);
    }
  }

  /**
   * Tests preparation for EntityAutocomplete::validateEntityAutocomplete.
   */
  public function testPrepareElementValuesForValidation() {
    $method = new ReflectionMethod($this::$testedClassName, 'prepareElementValuesForValidation');
    $method->setAccessible(TRUE);

    foreach ([FALSE, TRUE] as $tags) {
      $element = $original_element = [
        '#tags' => $tags,
        '#value' => [
          'Some value',
          'Another value',
        ],
      ];
      $method->invokeArgs(NULL, [&$element]);

      if ($tags) {
        $this->assertTrue(is_string($element['#value']));
      }
      else {
        $this->assertArrayEquals($original_element, $element);
      }
    }
  }

  /**
   * Tests if the widget correctly determines if it is applicable.
   */
  public function testIsApplicable() {
    $entityReferenceSelection = $this->getMockBuilder('Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $entityReferenceSelection->expects($this->exactly(2))
      ->method('getInstance')
      ->willReturnOnConsecutiveCalls(
        $this->getMockForAbstractClass('Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface'),
        $this->getMockForAbstractClass('Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface')
      );
    $this->containerMock->expects($this->any())
      ->method('get')
      ->with('plugin.manager.entity_reference_selection')
      ->willReturn($entityReferenceSelection);

    $definition = $this->getMockBuilder('Drupal\Core\Field\FieldDefinitionInterface')
      ->getMockForAbstractClass();
    $definition->expects($this->exactly(2))
      ->method('getSettings')
      ->willReturn(['handler_settings' => ['auto_create' => TRUE]]);
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $definition */
    $this->assertFalse(ReferenceWidget::isApplicable($definition));
    $this->assertTrue(ReferenceWidget::isApplicable($definition));
  }

  /**
   * Tests if the selected options are propery prepared.
   */
  public function testPrepareSelectedOptions() {
    $entityID = 1;
    $entityLabel = 'Label';
    $entityMock = $this->getMockBuilder('\Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $entityMock->expects($this->any())
      ->method('id')
      ->willReturn($entityID);
    $entityMock->expects($this->any())
      ->method('label')
      ->willReturn($entityLabel);

    $entityStorageMock = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityStorageInterface');
    $entityStorageMock->expects($this->exactly(2))
      ->method('loadMultiple')
      ->willReturnOnConsecutiveCalls([], [$entityMock]);

    $mock = $this->mockBuilder->disableOriginalConstructor()
      ->setMethods(['getEntityStorage'])
      ->getMock();
    $mock->expects($this->exactly(2))
      ->method('getEntityStorage')
      ->willReturn($entityStorageMock);

    $getOptions = new ReflectionMethod($mock, 'prepareSelectedOptions');
    $getOptions->setAccessible(TRUE);

    // First invocation returns an empty array because there are no entities.
    $options = $getOptions->invokeArgs($mock, [[]]);
    $expected = [];
    $this->assertArrayEquals($options, $expected);

    // Second invocation returns a value array..
    $options = $getOptions->invokeArgs($mock, [[]]);
    $expected = ["{$entityLabel} ({$entityID})"];
    $this->assertArrayEquals($options, $expected);
  }

}
