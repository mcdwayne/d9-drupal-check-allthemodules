<?php

namespace Drupal\Tests\commerce_funds\Unit\Services;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\commerce_price\Price;
use Drupal\commerce_payment\PaymentOption;
use Drupal\commerce_funds\Services\FeesManager;

/**
 * @coversDefaultClass \Drupal\commerce_funds\Services\FeesManager
 * @group commerce_funds
 */
class FeesManagerTest extends UnitTestCase {

  /**
   * The fee manager.
   *
   * @var \Drupal\commerce_funds\Services\FeeManager
   */
  protected $feeManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $configFactory = $this->getConfigFactoryStub([
      'commerce_funds.settings' => [
        'fees' => [
          'deposit_manual_rate' => '10',
          'deposit_manual_fixed' => '5',
          'transfer_rate' => '10',
          'transfer_fixed' => '5',
          'escrow_rate' => '10',
          'escrow_fixed' => '5',
        ],
        'exchange_rates' => [
          'USD_EUR' => '1.2',
          'EUR_USD' => '0.8',
        ]
      ],
    ]);
    $container = new ContainerBuilder();
    $container->set('config.factory', $configFactory);
    \Drupal::setContainer($container);

    $entityTypeManager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $paymentOptionsBuilder = $this->getMockBuilder('Drupal\commerce_payment\PaymentOptionsBuilderInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $order = $this->getMockBuilder('Drupal\commerce_order\Entity\OrderInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $options = new PaymentOption(['id' => 'manual', 'label' => 'Manual', 'payment_gateway_id' => 'manual']);
    $paymentOptionsBuilder->method('buildOptions')
      ->willReturn((array) $options);
    $paymentOptionsBuilder->method('selectDefaultOption')
      ->willReturn($options);

    $productManager = $this->getMockBuilder('Drupal\commerce_funds\Services\ProductManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->feeManager = new FeesManager($configFactory, $entityTypeManager, $paymentOptionsBuilder, $productManager);
  }

  /**
   * ::covers calculateOrderFee.
   */
  public function testCalculateOrderFee() {
    $payment_method = $this->prophesize(EntityReferenceFieldItemList::class);
    $payment_method->getValue()->willReturn('manual');
    $payment_method = $payment_method->reveal();
    $item = $this->prophesize(OrderItemInterface::class);
    $item->getTotalPrice()->willReturn(new Price('100', 'USD'));
    $item = $item->reveal();
    $order = $this->prophesize(Order::class);
    $order->get('payment_method')->willReturn($payment_method);
    $order->getItems()->willReturn([$item]);
    $order = $order->reveal();
    $this->assertEquals('10', $this->feeManager->calculateOrderFee($order));
  }

  /**
   * ::covers calculateOrderFee.
   */
  public function testCalculateTransactionFee() {
    $currency_code = 'USD';
    $existing_types = ['transfer', 'escrow'];
    // Fee rate > fixed fee.
    $brut_amount = 100;
    foreach ($existing_types as $type) {
      $this->assertEquals(110, $this->feeManager->calculateTransactionFee($brut_amount, $currency_code, $type)['net_amount']);
      $this->assertEquals(10, $this->feeManager->calculateTransactionFee($brut_amount, $currency_code, $type)['fee']);
    }
    // Fixed fee > fee rate.
    $brut_amount = 10;
    foreach ($existing_types as $type) {
      $this->assertEquals(15, $this->feeManager->calculateTransactionFee($brut_amount, $currency_code, $type)['net_amount']);
      $this->assertEquals(5, $this->feeManager->calculateTransactionFee($brut_amount, $currency_code, $type)['fee']);
    }
    // No fee set.
    $non_existing_types = ['withdrawal_request', 'payment', 'conversion'];
    foreach ($non_existing_types as $type) {
      $this->assertEquals(10, $this->feeManager->calculateTransactionFee($brut_amount, $currency_code, $type)['net_amount']);
      $this->assertEquals(0, $this->feeManager->calculateTransactionFee($brut_amount, $currency_code, $type)['fee']);
    }
  }

  /**
   * ::covers convertCurrencyAmount.
   */
  public function testConvertCurrencyAmount() {
    $this->assertEquals(120, $this->feeManager->convertCurrencyAmount(100, 'USD', 'EUR')['new_amount']);
    $this->assertEquals('1.2', $this->feeManager->convertCurrencyAmount('1.2', 'USD', 'EUR')['rate']);
    $this->assertEquals(80, $this->feeManager->convertCurrencyAmount(100, 'EUR', 'USD')['new_amount']);
    $this->assertEquals('0.8', $this->feeManager->convertCurrencyAmount('0.8', 'EUR', 'USD')['rate']);
  }

}
