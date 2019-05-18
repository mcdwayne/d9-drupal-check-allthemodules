<?php

namespace Drupal\Tests\xero\Unit\Plugin\DataType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\FloatData;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\xero\Plugin\DataType\Account;
use Drupal\xero\Plugin\DataType\Invoice;
use Drupal\xero\Plugin\DataType\Payment;
use Drupal\xero\TypedData\Definition\AccountDefinition;
use Drupal\xero\TypedData\Definition\InvoiceDefinition;
use Drupal\xero\TypedData\Definition\PaymentDefinition;

/**
 * Assert setting and getting Payment properties.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\DataType\XeroTypeBase
 * @group Xero
 */
class XeroTypeBaseTest extends TestBase {

  const XERO_TYPE = 'xero_payment';
  const XERO_TYPE_CLASS = '\Drupal\xero\Plugin\DataType\Payment';
  const XERO_DEFINITION_CLASS = '\Drupal\xero\TypedData\Definition\PaymentDefinition';

  /**
   * Test methods.
   */
  public function testMethods() {
    // Create data type.
    $type_class = self::XERO_TYPE_CLASS;
    $payment = new $type_class($this->dataDefinition, self::XERO_TYPE);

    $this->assertEquals('Payments', $payment->getPluralName());
    $this->assertEquals('Reference', $payment->getLabelName());

    $invoiceDefinition = new InvoiceDefinition(['id' => 'xero_invoice', 'definition class' => '\Drupal\xero\TypedData\Definition\InvoiceDefinition']);
    $invoice = new Invoice($invoiceDefinition, 'xero_invoice');
    $accountDefinition = new AccountDefinition(['id' => 'xero_account', 'definition class' => '\Drupal\xero\TypedData\Definition\AccountDefinition']);
    $account = new Account($accountDefinition, 'xero_account');
    $stringDefinition = new DataDefinition(['id' => 'string']);
    $date = new StringData($stringDefinition, 'string');
    $floatDefinition = new DataDefinition(['id' => 'float']);
    $amount = new FloatData($floatDefinition, 'float');

    $this->assertEquals('InvoiceID', $invoice->getGUIDName());

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->will($this->returnValue([
        [[$payment, 'Invoice', NULL], $invoice],
        [[$payment, 'Account', NULL], $account],
        [[$payment, 'Date', '2015-10-05'], $date],
        [[$payment, 'Amount', 10.0], $amount],
      ]));

    $payment->setValue([
      'Invoice' => NULL,
      'Account' => NULL,
      'Date' => '2015-10-05',
      'Amount' => 10.0
    ]);

    $expected = [
      '#theme' => 'xero_payment',
      '#item' => [
        'Invoice' => NULL,
        'Account' => NULL,
        'Date' => '2015-10-05',
        'Amount' => 10.0,
      ],
      '#attributes' => [
        'class' => ['xero-item', 'xero-item--payment']
      ],
    ];

    $this->assertEquals($expected, $payment->view());
  }

}
