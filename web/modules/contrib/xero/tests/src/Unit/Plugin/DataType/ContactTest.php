<?php

namespace Drupal\Tests\xero\Unit\Plugin\DataType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\xero\TypedData\Definition\ContactDefinition;
use Drupal\xero\Plugin\DataType\Contact;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;

/**
 * Assert setting and getting Contact properties.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\DataType\Contact
 * @group Xero
 */
class ContactTest extends TestBase {

  const XERO_TYPE = 'xero_contact';
  const XERO_TYPE_CLASS = '\Drupal\xero\Plugin\DataType\Contact';
  const XERO_DEFINITION_CLASS = '\Drupal\xero\TypedData\Definition\ContactDefinition';

  protected $contact;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create data type.
    $type_class = self::XERO_TYPE_CLASS;
    $this->contact = new $type_class($this->dataDefinition, self::XERO_TYPE);
  }

  /**
   * Test getPropertyDefinitions.
   */
  public function testPropertyDefinitions() {
    $properties = $this->contact->getProperties();

    $this->assertArrayHasKey('ContactID', $properties);
    $this->assertArrayHasKey('FirstName', $properties);
    $this->assertArrayHasKey('LastName', $properties);
    $this->assertArrayHasKey('EmailAddress', $properties);
    $this->assertArrayHasKey('SkypeUserName', $properties);
    $this->assertArrayHasKey('BankAccountDetails', $properties);
    $this->assertArrayHasKey('TaxNumber', $properties);
    $this->assertArrayHasKey('AccountsReceivableTaxType', $properties);
    $this->assertArrayHasKey('AccountsPayableTaxType', $properties);
    $this->assertArrayHasKey('Addresses', $properties);
    $this->assertArrayHasKey('Phones', $properties);
    $this->assertArrayHasKey('UpdatedDateUTC', $properties);
    $this->assertArrayHasKey('IsSupplier', $properties);
    $this->assertArrayHasKey('IsCustomer', $properties);
    $this->assertArrayHasKey('DefaultCurrency', $properties);
    $this->assertArrayHasKey('XeroNetworkKey', $properties);
    $this->assertArrayHasKey('Website', $properties);
    $this->assertArrayHasKey('BrandingTheme', $properties);
  }

  /**
   * Test isSupplier method.
   */
  public function testIsSupplier() {
    $bool_def = DataDefinition::create('boolean');
    $bool = new BooleanData($bool_def);

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($this->contact, 'IsSupplier')
      ->willReturn($bool);

    $this->contact->set('IsSupplier', FALSE);
    $this->assertFalse($this->contact->isSupplier());

    $this->contact->set('IsSupplier', TRUE);
    $this->assertTrue($this->contact->isSupplier());
  }

  /**
   * Test isCustomer method.
   */
  public function testIsCustomer() {
    $bool_def = DataDefinition::create('boolean');
    $bool = new BooleanData($bool_def);

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($this->contact, 'IsCustomer')
      ->willReturn($bool);

    $this->contact->set('IsCustomer', FALSE);
    $this->assertFalse($this->contact->isCustomer());

    $this->contact->set('IsCustomer', TRUE);
    $this->assertTrue($this->contact->isCustomer());
  }
}
