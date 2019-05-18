<?php

namespace Drupal\Tests\commerce_xero\Kernel\Plugin\CommerceXero\processor;

use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_xero\Plugin\CommerceXero\processor\TrackingCategory;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\xero\Plugin\DataType\BankTransaction;
use Drupal\xero\TypedData\Definition\BankTransactionDefinition;

/**
 * Tests the process method of TrackingCategory plugin.
 *
 * @group commerce_xero
 */
class TrackingCategoryTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_xero',
    'xero',
    'serialization',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');

    PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ])->save();
  }

  /**
   * Asserts that the process method alters the bank transaction.
   *
   * @covers \Drupal\commerce_xero\Plugin\CommerceXero\processor\TrackingCategory::process
   */
  public function testProcess() {
    $configuration = [
      'id' => 'commerce_xero_tracking_category',
      'settings' => [
        'tracking_category' => 'Region',
        'tracking_option' => 'East Coast',
      ],
    ];
    $definition = [
      'id' => 'commere_xero_tracking_category',
      'label' => 'Tracking Category',
      'types' => ['xero_bank_transaction'],
      'settings' => [],
      'execution' => 'immediate',
      'required' => FALSE,
      'class' => '\Drupal\commerce_xero\Plugin\CommerceXero\processor\TrackingCategory',
    ];

    // Mocks xero.query which is unused here, but needed to instantiate.
    $queryProphet = $this->prophesize('\Drupal\xero\XeroQuery');
    $query = $queryProphet->reveal();

    $this->container->set('xero.query', $query);

    $plugin = TrackingCategory::create(
      $this->container,
      $configuration,
      'commerce_xero_tracking_category',
      $definition
    );

    // Creates payment entity and bank transaction.
    $transaction_values = [
      'Contact' => ['Name' => 'Test'],
      'Type' => 'RECEIVE',
      'Date' => '2019-01-02T00:00:00',
      'LineAmountTypes' => 'Exclusive',
      'LineItems' => [
        [
          'Description' => 'Test Transaction',
          'Quantity' => 1,
          'ItemCode' => 'TEST',
          'UnitAmount' => '1.00',
        ],
      ],
      'BankAccount' => [
        'Code' => '090',
      ],
    ];
    $payment = Payment::create([
      'payment_gateway' => 'example',
      'payment_method' => 'cash',
    ]);
    $transactionDefinition = BankTransactionDefinition::create('xero_bank_transaction');
    $data = BankTransaction::createInstance($transactionDefinition);
    $data->setValue($transaction_values);

    $plugin->process($payment, $data);
    $expected = [
      [
        'Name' => 'Region',
        'Option' => 'East Coast',
      ],
    ];

    $tracking = $data
      ->get('LineItems')
      ->get(0)
      ->get('Tracking');
    $this->assertEquals($expected, $tracking->getValue());
  }

}
