<?php

namespace Drupal\Tests\xero\Unit\Plugin\Field\FieldWidget;

use Drupal\xero\Plugin\Field\FieldType\XeroReference;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Provides a base test class for testing field widgets.
 */
abstract class WidgetTestBase extends UnitTestCase {

  /**
   * The plugin id of the widget.
   *
   * @return string
   *   The plugin id of the widget.
   */
  abstract protected function getPluginId();

  /**
   * The plugin class of the widget.
   *
   * @return string
   *   The plugin class of the widget.
   */
  abstract protected function getPluginClass();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // DrupalWTF: t()
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
      // ->with($this->anything())
      ->will($this->returnValueMap($this->getDefinitionMap()));

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
      'class' => $this->getPluginClass(),
    ];
    $configuration = [
      'field_definition' => $this->fieldDefinition,
      'settings' => array(),
      'label' => $this->getRandomGenerator()->word(10),
      'view_mode' => 'default',
      'third_party_settings' => array(),
    ];

    $class = $this->getPluginClass();

    if (in_array('Drupal\Core\Plugin\ContainerFactoryPluginInterface', class_implements($class))) {
      $this->widget = $class::create($container, $configuration, $this->getPluginId(), $plugin_definition);
    }
    else {
      $this->widget = new $class($this->getPluginId(), $plugin_definition, $this->fieldDefinition, $configuration['settings'], $configuration['third_party_settings']);
    }
    $this->fieldItemList = new FieldItemList($this->fieldDefinition);
    $this->fieldItem = new XeroReference($this->fieldDefinition);

    $this->fieldDefinition->setSetting('xero_type', array('xero_employee' => 'xero_employee'));
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

  /**
   * Get definition map for getDefinition call moking.
   *
   * @return []
   *   An array of argument and return value for getDefinition().
   */
  protected function getDefinitionMap() {
    return [
      ['field_item:xero_reference', TRUE, ['class' => '\Drupal\xero\Plugin\Field\FieldType\XeroReference']],
      ['xero_account', TRUE, ['label' => 'Xero Account', 'class' => '\Drupal\xero\Plugin\DataType\Account']],
      ['xero_bank_transaction', TRUE, ['label' => 'Xero Bank Transaction', 'class' => '\Drupal\xero\Plugin\DataType\BankTransaction']],
      ['xero_contact', TRUE, ['label' => 'Xero Contact', 'class' => '\Drupal\xero\Plugin\DataType\Contact']],
      ['xero_customer', TRUE, ['label' => 'Xero Customer', 'class' => '\Drupal\xero\Plugin\DataType\Customer']],
      ['xero_credit_note', TRUE, ['label' => 'Xero Credit Note', 'class' => '\Drupal\xero\Plugin\DataType\CreditNote']],
      ['xero_employee', TRUE, ['label' => 'Xero Employee', 'class' => '\Drupal\xero\Plugin\DataType\Employee']],
      ['xero_expense', TRUE, ['label' => 'Xero Expense', 'class' => '\Drupal\xero\Plugin\DataType\Expense']],
      ['xero_invoice', TRUE, ['label' => 'Xero Invoice', 'class' => '\Drupal\xero\Plugin\DataType\Invoice']],
      ['xero_journal', TRUE, ['label' => 'Xero Journal', 'class' => '\Drupal\xero\Plugin\DataType\Journal']],
      ['xero_payment', TRUE, ['label' => 'Xero Payment', 'class' => '\Drupal\xero\Plugin\DataType\Payment']],
      ['xero_receipt', TRUE, ['label' => 'Xero Receipt', 'class' => '\Drupal\xero\Plugin\DataType\Receipt']],
      ['xero_user', TRUE, ['label' => 'Xero User', 'class' => '\Drupal\xero\Plugin\DataType\User']],
    ];
  }

}
