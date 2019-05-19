<?php

namespace Drupal\Tests\xero\Unit\Plugin\Field\FieldFormatter;

use Drupal\xero\Plugin\Field\FieldType\XeroReference;
use Drupal\xero\Plugin\Field\FieldFormatter\XeroReferenceFormatter;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormState;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;

/**
 * Test the formatter plugin. This does not use BaseFieldDefinitionTestBase
 * because that class is terrible, and is full of DrupalWTF crap such as not
 * setting useful things as properties such as typed data manager.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\Field\FieldFormatter\XeroReferenceFormatter
 * @group Xero
 */
class XeroReferenceFormatterTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // DrupalWTF. t().
    require_once realpath($this->root . '/core/includes/bootstrap.inc');

    $container = new ContainerBuilder();

    // Mock Typed Data Manager
    $this->typedDataManager = $this->getMockBuilder('\Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->typedDataManager->expects($this->any())
      ->method('getDefaultConstraints')
      ->willReturn([]);
    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->anything())
      ->will($this->onConsecutiveCalls(
        ['class' => '\Drupal\xero\Plugin\Field\FieldType\XeroReference'],
        ['class' => '\Drupal\xero\Plugin\DataType\Employee']
      ));

    // Mock Field Type Plugin Manager
    $this->pluginManager = $this->getMockBuilder('\Drupal\Core\Field\FieldTypePluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->pluginManager->expects($this->any())
      ->method('getDefaultStorageSettings')
      ->with('xero_reference')
      ->willReturn([]);
    $this->pluginManager->expects($this->any())
      ->method('getDefaultFieldSettings')
      ->with('xero_reference')
      ->willReturn([]);

    // Validation constraint manager setup.
    $validation_constraint_manager = $this->getMockBuilder('\Drupal\Core\Validation\ConstraintManager')
      ->disableOriginalConstructor()
      ->getMock();
    $validation_constraint_manager->expects($this->any())
      ->method('create')
      ->willReturn([]);
    $this->typedDataManager->expects($this->any())
      ->method('getValidationConstraintManager')
      ->willReturn($validation_constraint_manager);

    // Set the container again to get rid of stupid base class stuff.
    $container->set('typed_data_manager', $this->typedDataManager);
    $container->set('plugin.manager.field.field_type', $this->pluginManager);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // Field definition
    $this->fieldDefinition = BaseFieldDefinition::create('xero_reference');

    // Formatter configuration.
    $plugin_definition = [
      'class' => '\Drupal\xero\Plugin\Field\FieldFormatter\XeroReferenceFormatter'
    ];
    $configuration = [
      'field_definition' => $this->fieldDefinition,
      'settings' => array(),
      'label' => $this->getRandomGenerator()->word(10),
      'view_mode' => 'default',
      'third_party_settings' => array(),
    ];

    $this->formatter = XeroReferenceFormatter::create($container, $configuration, 'xero_reference', $plugin_definition);
    $this->fieldItemList = new FieldItemList($this->fieldDefinition);
    $this->fieldItem = new XeroReference($this->fieldDefinition);
  }

  /**
   * Test Formatter class.
   */
  public function testFormatterClass() {
    $values = array(
      'guid' => $this->createGuid(),
      'type' => 'xero_employee',
      'label' => $this->getRandomGenerator()->word(15),
    );

    $this->fieldItem->setValue($values, FALSE);

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($this->fieldItemList, 0, $values)
      ->willReturn($this->fieldItem);
    $this->pluginManager->expects($this->any())
      ->method('createFieldItem')
      ->with($this->fieldItemList, 0, $values)
      ->willReturn($this->fieldItem);

    $this->fieldItemList->setValue([0 => $values]);

    $render = $this->formatter->viewElements($this->fieldItemList, Language::LANGCODE_NOT_SPECIFIED);

    $this->assertEquals(1, count($render));
    $this->assertTrue(is_a($render[0]['#item'], 'Drupal\xero\Plugin\Field\FieldType\XeroReference'));
  }

  /**
   * Test Formatter Settings.
   */
  public function testFormatterSettings() {
    $form = [];
    $formState = new FormState();

    $settingsSummary = $this->formatter->settingsSummary();
    $this->assertEquals(3, count($settingsSummary));
    $this->assertEquals('Guid: Visible', $settingsSummary[0]);
    $this->assertEquals('Type: Visible', $settingsSummary[1]);
    $this->assertEquals('Label: Visible', $settingsSummary[2]);

    $this->formatter->setSetting('display', array());
    $settingsSummary = $this->formatter->settingsSummary();

    $this->assertEquals('Guid: Hidden', $settingsSummary[0]);
    $this->assertEquals('Type: Hidden', $settingsSummary[1]);
    $this->assertEquals('Label: Hidden', $settingsSummary[2]);

    $settingsForm = $this->formatter->settingsForm($form, $formState);
    $this->assertEquals([], $settingsForm['display']['#default_value']);
  }

  /**
   * Create a Guid with or without curly braces.
   *
   * @param $braces
   *   (Optional) Return Guid wrapped in curly braces.
   * @return string
   *   Guid string.
   */
  protected function createGuid($braces = TRUE) {
    $hash = strtolower(hash('ripemd128', md5($this->getRandomGenerator()->string(100))));
    $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4);
    $guid .= '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);

    if ($braces) {
      return '{' . $guid . '}';
    }

    return $guid;
  }

}
