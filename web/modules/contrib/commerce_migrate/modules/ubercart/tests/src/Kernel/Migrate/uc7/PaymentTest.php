<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests payment migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class PaymentTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_payment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'migrate_plus',
    'node',
    'path',
    'profile',
    'state_machine',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_payment');
    PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'manual',
    ])->save();

    $this->migrateOrders();
    $this->executeMigrations([
      'uc_payment_gateway',
      'uc7_payment',
    ]);

  }

  /**
   * Tests payment migration.
   */
  public function testPayment() {
    $payment = [
      'id' => 1,
      'order_id' => '1',
      'type' => 'payment_manual',
      'payment_gateway' => 'cod',
      'payment_method' => NULL,
      'amount_number' => '50.000000',
      'amount_currency_code' => 'USD',
      'refunded_amount_number' => '50.000000',
      'refunded_amount_currency_code' => 'USD',
      'balance_number' => '0',
      'balance_currency_code' => 'USD',
      'label_value' => 'refunded',
      'label_rendered' => 'Refunded',
    ];
    $this->assertPaymentEntity($payment);
    $payment = [
      'id' => 3,
      'order_id' => '1',
      'type' => 'payment_manual',
      'payment_gateway' => 'cod',
      'payment_method' => NULL,
      'amount_number' => '53.000000',
      'amount_currency_code' => 'USD',
      'refunded_amount_number' => '50.000000',
      'refunded_amount_currency_code' => 'USD',
      'balance_number' => '3',
      'balance_currency_code' => 'USD',
      'label_value' => 'partially_refunded',
      'label_rendered' => 'Partially refunded',
    ];
    $this->assertPaymentEntity($payment);
    $payment = [
      'id' => 5,
      'order_id' => '2',
      'type' => 'payment_manual',
      'payment_gateway' => 'cod',
      'payment_method' => NULL,
      'amount_number' => '400.000000',
      'amount_currency_code' => 'USD',
      'refunded_amount_number' => '0.000000',
      'refunded_amount_currency_code' => 'USD',
      'balance_number' => '400',
      'balance_currency_code' => 'USD',
      'label_value' => 'new',
      'label_rendered' => 'New',
    ];
    $this->assertPaymentEntity($payment);
    $payment = [
      'id' => 6,
      'order_id' => '2',
      'type' => 'payment_manual',
      'payment_gateway' => 'cod',
      'payment_method' => NULL,
      'amount_number' => '40.400000',
      'amount_currency_code' => 'USD',
      'refunded_amount_number' => '0.000000',
      'refunded_amount_currency_code' => 'USD',
      'balance_number' => '40.4',
      'balance_currency_code' => 'USD',
      'label_value' => 'new',
      'label_rendered' => 'New',
    ];
    $this->assertPaymentEntity($payment);

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->getMigration('uc7_payment');

    // Check that we've reported the refund in excess of payments.
    $messages = [];
    foreach ($migration->getIdMap()->getMessageIterator() as $message_row) {
      $messages[] = $message_row->message;
    }
    $this->assertCount(1, $messages);
    $this->assertSame('Refund exceeds payments for payment 7', $messages[0]);
  }

}
