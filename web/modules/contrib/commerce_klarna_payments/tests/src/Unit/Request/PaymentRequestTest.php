<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\CustomerInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\OptionsInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\UrlsetInterface;
use Drupal\commerce_klarna_payments\Klarna\Request\Payment\Request;
use Drupal\Tests\UnitTestCase;

/**
 * Payment request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\Payment\Request
 */
class PaymentRequestTest extends UnitTestCase {

  /**
   * Tests setLocale().
   *
   * @covers ::setLocale
   * @expectedException \InvalidArgumentException
   */
  public function testSetLocaleException() {
    $request = new Request();
    $request->setLocale('fi-sv');
  }

  /**
   * Tests setLocale().
   *
   * @covers ::setLocale
   * @dataProvider localeData
   */
  public function testSetLocale(string $locale, array $additional) {
    $requqest = new Request();
    $requqest->setLocale($locale, $additional);
    $this->assertEquals(['locale' => $locale], $requqest->toArray());
  }

  /**
   * Data provider for locale tests.
   *
   * @return array
   *   The data.
   */
  public function localeData() {
    return [
      [
        'fi-fi', [],
      ],
      [
        'sv-sv', ['sv-sv' => 'SV'],
      ],
    ];
  }

  /**
   * Tests toArray().
   *
   * @covers ::setDesign
   * @covers ::setLocale
   * @covers ::setPurchaseCountry
   * @covers ::setPurchaseCurrency
   * @covers ::setOrderAmount
   * @covers ::setOrderTaxAmount
   * @covers ::setMerchantData
   * @covers ::setMerchantReference2
   * @covers ::setMerchantReference1
   * @covers ::setCustomPaymentMethodIds
   * @covers ::toArray
   * @dataProvider toArrayData
   */
  public function testToArray(array $expected) {
    $request = new Request();
    $request->setDesign($expected['design'])
      ->setLocale($expected['locale'])
      ->setPurchaseCurrency($expected['purchase_currency'])
      ->setPurchaseCountry($expected['purchase_country'])
      ->setOrderAmount($expected['order_amount'])
      ->setOrderTaxAmount($expected['order_tax_amount'])
      ->setMerchantReference1($expected['merchant_reference1'])
      ->setMerchantReference2($expected['merchant_reference2'])
      ->setMerchantData($expected['merchant_data'])
      ->setCustomPaymentMethodIds($expected['custom_payment_method_ids']);

    $this->assertEquals($expected, $request->toArray());
  }

  /**
   * @covers ::setOrderItems
   * @covers ::addOrderItem
   */
  public function testSetOrderItem() {
    $orderItem = $this->getMockBuilder(OrderItemInterface::class)
      ->getMock();
    $orderItem->expects($this->any())
      ->method('toArray')
      ->willReturn([
        'name' => '123',
      ]);

    $request = new Request();
    $request->setOrderItems([$orderItem]);

    $orderLine = ['name' => '123'];

    // Test with one order lines.
    $this->assertEquals(['order_lines' => [$orderLine]], $request->toArray());

    $request->addOrderItem($orderItem);
    // Test with multiple order lines.
    $this->assertEquals(['order_lines' => [$orderLine, $orderLine]], $request->toArray());
  }

  /**
   * @covers ::setBillingAddress
   * @covers ::setShippingAddress
   */
  public function testSetAddress() {
    $address = $this->getMockBuilder(AddressInterface::class)
      ->getMock();
    $address->expects($this->any())
      ->method('toArray')
      ->willReturn([
        'title' => 'test',
      ]);

    $request = (new Request())
      ->setBillingAddress($address)
      ->setShippingAddress($address);

    $this->assertEquals([
      'shipping_address' => ['title' => 'test'],
      'billing_address' => ['title' => 'test'],
    ], $request->toArray());
  }

  /**
   * @covers ::setCustomer
   */
  public function testSetCustomer() {
    $customer = $this->getMockBuilder(CustomerInterface::class)
      ->getMock();
    $customer->expects($this->any())
      ->method('toArray')
      ->willReturn([
        'type' => 'unicorn',
      ]);

    $request = (new Request())->setCustomer($customer);

    $this->assertEquals([
      'customer' => [
        'type' => 'unicorn',
      ],
    ], $request->toArray());
  }

  /**
   * @covers ::setMerchantUrls
   */
  public function testSetMerchantUrls() {
    $urlSet = $this->getMockBuilder(UrlsetInterface::class)
      ->getMock();

    $urlSet->expects($this->any())
      ->method('toArray')
      ->willReturn([
        'confirmation' => 'http://localhost/confirm',
      ]);

    $request = (new Request())->setMerchantUrls($urlSet);

    $this->assertEquals([
      'merchant_urls' => [
        'confirmation' => 'http://localhost/confirm',
      ],
    ], $request->toArray());
  }

  /**
   * @covers ::setOptions
   */
  public function testSetOptions() {
    $options = $this->getMockBuilder(OptionsInterface::class)
      ->getMock();
    $options->expects($this->any())
      ->method('toArray')
      ->willReturn([
        'border_radius' => '5',
      ]);

    $request = (new Request())->setOptions($options);

    $this->assertEquals([
      'options' => ['border_radius' => '5'],
    ], $request->toArray());
  }

  /**
   * @covers ::setAttachment
   */
  public function testSetAttachment() {
    $attachment = $this->getMockBuilder(AttachmentInterface::class)
      ->getMock();
    $attachment->expects($this->any())
      ->method('toArray')
      ->willReturn([
        'content_type' => 'test',
      ]);

    $request = (new Request())->setAttachment($attachment);

    $this->assertEquals([
      'attachment' => ['content_type' => 'test'],
    ], $request->toArray());
  }

  /**
   * Data provider for testToArray().
   *
   * @return array
   *   The data.
   */
  public function toArrayData() {
    return [
      [
        [
          'design' => 'design',
          'purchase_country' => 'FI',
          'purchase_currency' => 'EUR',
          'locale' => 'fi-fi',
          'order_amount' => 1000,
          'order_tax_amount' => 250,
          'merchant_reference1' => 'ref1',
          'merchant_reference2' => 'ref2',
          'merchant_data' => 'Merchant data',
          'custom_payment_method_ids' => [1, 2, 3],
        ],
      ],
    ];
  }

}