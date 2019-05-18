<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests payment migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class PaymentTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_payment',
    'commerce_price',
    'commerce_product',
    'commerce_shipping',
    'commerce_store',
    'migrate_plus',
    'path',
    'physical',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateOrders();
    $this->installEntitySchema('commerce_payment');
    // @todo Execute the d7_field and d7_field_instance migrations?
    $this->executeMigrations([
      'commerce1_payment_gateway',
      'commerce1_payment',
    ]);
  }

  /**
   * Asserts a payment entity.
   *
   * @param array $payment
   *   An array of payment information.
   *   - The payment id.
   *   - The order id for this payment.
   *   - The payment type.
   *   - The gateway id.
   *   - The payment method.
   *   - The payment amount.
   *   - The payment currency code.
   *   - The order balance.
   *   - The order balance currency code.
   *   - The refunded amount.
   *   - The refunded amount currency code.
   */
  private function assertPaymentEntity(array $payment) {
    $payment_instance = Payment::load($payment['id']);
    $this->assertInstanceOf(Payment::class, $payment_instance);
    $this->assertSame($payment['order_id'], $payment_instance->getOrderId());
    $this->assertSame($payment['type'], $payment_instance->getType()->getPluginId());
    $this->assertSame($payment['payment_gateway'], $payment_instance->getPaymentGatewayId());
    $this->assertSame($payment['payment_method'], $payment_instance->getPaymentMethodId());
    $formatted_number = $this->formatNumber($payment['amount_number'], $payment_instance->getAmount()->getNumber());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertSame($payment['amount_currency_code'], $payment_instance->getAmount()->getCurrencyCode());
    $formatted_number = $this->formatNumber($payment['balance_number'], $payment_instance->getBalance()->getNumber());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertSame($payment['balance_currency_code'], $payment_instance->getBalance()->getCurrencyCode());
    $formatted_number = $this->formatNumber($payment['refunded_amount_number'], $payment_instance->getRefundedAmount()->getNumber());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertSame($payment['refunded_amount_currency_code'], $payment_instance->getRefundedAmount()->getCurrencyCode());
    $this->assertSame($payment['label_value'], $payment_instance->getState()->value);
    $state_label = $payment_instance->getState()->getLabel();
    $label = NULL;
    if (is_string($state_label)) {
      $label = $state_label;
    }
    elseif ($state_label instanceof TranslatableMarkup) {
      $arguments = $state_label->getArguments();
      $label = isset($arguments['@label']) ? $arguments['@label'] : $state_label->render();
    }
    $this->assertSame($payment['label_rendered'], $label);
  }

  /**
   * Test line item migration from Drupal 7 to 8.
   */
  public function testPayment() {
    $payment = [
      'id' => 1,
      'order_id' => '2',
      'type' => 'payment_manual',
      'payment_gateway' => 'commerce_payment_example',
      'payment_method' => NULL,
      'amount_number' => '12000.000000',
      'amount_currency_code' => 'USD',
      'refunded_amount_number' => '0.000000',
      'refunded_amount_currency_code' => 'USD',
      'balance_number' => '12000',
      'balance_currency_code' => 'USD',
      'label_value' => 'success',
      'label_rendered' => 'success',
      'created' => '1493287432',
      'changed' => '1493287450',
    ];
    $this->assertPaymentEntity($payment);
    $payment = [
      'id' => 2,
      'order_id' => '3',
      'type' => 'payment_manual',
      'payment_gateway' => 'commerce_payment_example',
      'payment_method' => NULL,
      'amount_number' => '3999.000000',
      'amount_currency_code' => 'USD',
      'refunded_amount_number' => '0.000000',
      'refunded_amount_currency_code' => 'USD',
      'balance_number' => '3999',
      'balance_currency_code' => 'USD',
      'label_value' => 'success',
      'label_rendered' => 'success',
      'created' => '1493287432',
      'changed' => '1493287450',
    ];
    $this->assertPaymentEntity($payment);
  }

}
