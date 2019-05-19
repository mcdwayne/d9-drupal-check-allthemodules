<?php

namespace src\TypedData;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Assert that Xero TypedData definition classes are functional.
 *
 * @group Xero
 */
class XeroDefinitionTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Typed Data Manager setup.
    $this->typedDataManager = $this->getMockBuilder('\Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();

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

    // Mock the container.
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $this->typedDataManager);
    \Drupal::setContainer($container);
  }

  /**
   * Assert that Typed Data definitions are defined correctly.
   *
   * @param string $xero_type
   *   The plugin id of the xero data type.
   * @param string $definition_name
   *   The base name of the definition class.
   * @param string $property
   *   A property check on the definition.
   *
   * @dataProvider definitionProvider
   */
  public function testDefinition($xero_type, $definition_name, $property) {

    $definition_class = '\Drupal\xero\TypedData\Definition\\' . $definition_name;

    /** @var \Drupal\Core\TypedData\ComplexDataDefinitionInterface $definition */
    $definition = $definition_class::create($xero_type);

    $this->assertArrayHasKey($property, $definition->getPropertyDefinitions());
  }

  /**
   * Provide definition class and expected output.
   *
   * @return array
   *   An array of parameters to pass ino the test function.
   */
  public function definitionProvider() {
    return [
      ['xero_account', 'AccountDefinition', 'AccountID'],
      ['xero_address', 'AddressDefinition', 'AddressType'],
      ['xero_bank_transaction', 'BankTransactionDefinition', 'BankTransactionID'],
      ['xero_bank_transfer', 'BankTransferDefinition', 'BankTransferID'],
      ['xero_branding_theme', 'BrandingThemeDefinition', 'BrandingThemeID'],
      ['xero_contact', 'ContactDefinition', 'ContactID'],
      ['xero_contact_group', 'ContactGroupDefinition', 'ContactGroupID'],
      ['xero_credit_note', 'CreditDefinition', 'CreditNoteID'],
      ['xero_currency', 'CurrencyDefinition', 'Code'],
      ['xero_detail', 'DetailDefinition', 'UnitPrice'],
      ['xero_employee', 'EmployeeDefinition', 'EmployeeID'],
      ['xero_expense', 'ExpenseDefinition', 'ExpenseClaimID'],
      ['xero_invoice', 'InvoiceDefinition', 'InvoiceID'],
      ['xero_invoice_reminder', 'InvoiceReminderDefinition', 'Enabled'],
      ['xero_item', 'ItemDefinition', 'Code'],
      ['xero_journal', 'JournalDefinition', 'JournalID'],
      ['xero_journal_line', 'JournalLineDefinition', 'NetAmount'],
      ['xero_line_item', 'LineItemDefinition', 'Description'],
      ['xero_link', 'LinkDefinition', 'Url'],
      ['xero_linked_transaction', 'LinkedTransactionDefinition', 'LinkedTransactionID'],
      ['xero_organisaion', 'OrganisationDefinition', 'Name'],
      ['xero_payment', 'PaymentDefinition', 'Reference'],
      ['xero_phone', 'PhoneDefinition', 'PhoneType'],
      ['xero_receipt', 'ReceiptDefinition', 'ReceiptID'],
      ['xero_repeating_invoice', 'RepeatingInvoiceDefinition', 'RepeatingInvoiceID'],
      ['xero_schedule', 'ScheduleDefinition', 'Period'],
      ['xero_tax_component', 'TaxComponentDefinition', 'Name'],
      ['xero_tax_rate', 'TaxRateDefinition', 'Name'],
      ['xero_tracking', 'TrackingCategoryDefinition', 'Name'],
      ['xero_tracking_option', 'TrackingOptionDefinition', 'Name'],
      ['xero_user', 'UserDefinition', 'UserID'],
    ];
  }
}