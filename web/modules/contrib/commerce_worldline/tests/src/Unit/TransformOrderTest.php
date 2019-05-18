<?php

namespace Drupal\Tests\commerce_worldline\Unit;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_worldline\TransformOrder;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Sips\PaymentRequest;

/**
 * Class TransformOrderTest.
 *
 * @group commerce_worldline
 */
class TransformOrderTest extends UnitTestCase {

  /**
   * The relevant part of the plugin configuration array.
   *
   * @var array
   */
  protected $config;

  /**
   * The payment.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->config = [
      'sips_passphrase' => 'llama',
      'mode' => 0,
      'sips_merchant_id' => 'owl',
      'sips_key_version' => 1,
    ];

    $this->order = $this->getMock(OrderInterface::class);
    $this->order->expects($this->any())
      ->method('getTotalPrice')
      ->willReturn(new Price('2000', 'EUR'));
    $this->order->expects($this->any())
      ->method('language')
      ->willReturn(new Language(['id' => 'nl']));
  }

  /**
   * Tests basic transformation.
   */
  public function testTransformation() {
    $testTransformer = new TransformOrder();

    $transformed = $testTransformer->toPaymentRequest($this->config, $this->order, 'http://www.dazzle.be', 12, 'VISA');

    $this->assertInstanceOf(PaymentRequest::class, $transformed);
    $this->assertEquals(200000, $transformed->toArray()['amount']);
    $this->assertEquals('nl', $transformed->toArray()['customerLanguage']);
    $this->assertEquals('VISA', $transformed->toArray()['paymentMeanBrandList']);
    $this->assertEquals('owl', $transformed->toArray()['merchantId']);
    $this->assertEquals('http://www.dazzle.be', $transformed->toArray()['normalReturnUrl']);
    $this->assertEquals('1', $transformed->toArray()['keyVersion']);
  }

  /**
   * Tests the customer language.
   */
  public function testCustomerLanguage() {
    $testTransformer = new TransformOrder();

    $order = $this->getMock(OrderInterface::class);
    $order->expects($this->atLeastOnce())
      ->method('getTotalPrice')
      ->willReturn(new Price('2000', 'EUR'));
    $nl_order = clone $order;
    $nl_order->expects($this->atLeastOnce())
      ->method('language')
      ->willReturn(new Language(['id' => 'nl']));

    $transformed = $testTransformer->toPaymentRequest($this->config, $nl_order, 'http://www.dazzle.be', 1);
    $this->assertEquals('nl', $transformed->toArray()['customerLanguage']);

    $en_order = clone $order;
    $en_order->expects($this->atLeastOnce())
      ->method('language')
      ->willReturn(new Language(['id' => 'en']));

    $transformed = $testTransformer->toPaymentRequest($this->config, $en_order, 'http://www.dazzle.be', 2);
    $this->assertEquals('en', $transformed->toArray()['customerLanguage']);

    $it_order = clone $order;
    $it_order->expects($this->atLeastOnce())
      ->method('language')
      ->willReturn(new Language(['id' => 'it']));

    $transformed = $testTransformer->toPaymentRequest($this->config, $it_order, 'http://www.dazzle.be', 3);
    $this->assertEquals('it', $transformed->toArray()['customerLanguage']);

    $invalid_lang_order = clone $order;
    $invalid_lang_order->expects($this->atLeastOnce())
      ->method('language')
      ->willReturn(new Language(['id' => 'milk']));

    $transformed = $testTransformer->toPaymentRequest($this->config, $invalid_lang_order, 'http://www.dazzle.be', 4);
    $this->assertEquals('en', $transformed->toArray()['customerLanguage']);
  }

  /**
   * Tests no brand selection.
   */
  public function testNoBrandSelection() {
    $testTransformer = new TransformOrder();

    $transformed = $testTransformer->toPaymentRequest($this->config, $this->order, 'http://www.dazzle.be', 4);
    $this->assertInstanceOf(PaymentRequest::class, $transformed);
    $this->assertArrayNotHasKey('paymentMeanBrandList', $transformed->toArray());
  }

}
