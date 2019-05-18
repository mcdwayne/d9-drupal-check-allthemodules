<?php

namespace Drupal\Tests\xero\Unit\Plugin\DataType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\xero\TypedData\Definition\AccountDefinition;
use Drupal\xero\Plugin\DataType\Account;
use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Assert setting and getting Account properties.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\DataType\Account
 * @group Xero
 */
class AccountTest extends TestBase {

  const XERO_TYPE = 'xero_account';
  const XERO_TYPE_CLASS = '\Drupal\xero\Plugin\DataType\Account';
  const XERO_DEFINITION_CLASS = '\Drupal\xero\TypedData\Definition\AccountDefinition';

  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create data type.
    $type_class = self::XERO_TYPE_CLASS;
    $this->account = new $type_class($this->dataDefinition, self::XERO_TYPE);
  }

  /**
   * Test getPropertyDefinitions.
   */
  public function testPropertyDefinitions() {
    $properties = $this->account->getProperties();

    $this->assertArrayHasKey('AccountID', $properties);
    $this->assertArrayHasKey('Code', $properties);
    $this->assertArrayHasKey('Name', $properties);
    $this->assertArrayHasKey('Type', $properties);
    $this->assertArrayHasKey('Description', $properties);
    $this->assertArrayHasKey('TaxType', $properties);
    $this->assertArrayHasKey('EnablePaymentsToAccount', $properties);
    $this->assertArrayHasKey('ShowInExpenseClaims', $properties);
    $this->assertArrayHasKey('Class', $properties);
    $this->assertArrayHasKey('Status', $properties);
    $this->assertArrayHasKey('SystemAccount', $properties);
    $this->assertArrayHasKey('BankAccountNumber', $properties);
    $this->assertArrayHasKey('CurrencyCode', $properties);
    $this->assertArrayHasKey('ReportingCode', $properties);
    $this->assertArrayHasKey('ReportingCodeName', $properties);
  }

  /**
   * Test isRevenue method.
   */
  public function testAccountClass() {

    $string_def = DataDefinition::create('string');
    $string = new StringData($string_def);

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($this->account, 'Class')
      ->willReturn($string);

    $this->account->set('Class', 'EXPENSE');
    $this->assertFalse($this->account->isRevenue());

    $this->account->set('Class', 'REVENUE');
    $this->assertTrue($this->account->isRevenue());

    $this->account->set('Class', FALSE);
    $this->setExpectedException('Exception');
    $this->account->isRevenue();
  }

  /**
   * Test isBankAccount method.
   */
  public function testType() {
    $string_def = DataDefinition::create('string');
    $string = new StringData($string_def);

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($this->account, 'Type')
      ->willReturn($string);

    $this->account->set('Type', 'SALES');
    $this->assertFalse($this->account->isBankAccount());

    $this->account->set('Type', 'BANK');
    $this->assertTrue($this->account->isBankAccount());
  }
}
