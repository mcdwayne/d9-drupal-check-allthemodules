<?php

namespace Drupal\Tests\commerce_klarna_payments\Kernel;

use Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;

/**
 * Request builder tests.
 *
 * @group commerce_klarna_paymnents
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Service\Payment\RequestBuilder
 */
class RequestBuilderTest extends KlarnaKernelBase {

  /**
   * The request builder.
   *
   * @var \Drupal\commerce_klarna_payments\Klarna\Service\Payment\RequestBuilder
   */
  protected $sut;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // An order item type that doesn't need a purchasable entity, for
    // simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    $this->sut = $this->container->get('commerce_klarna_payments.request_builder');
  }

  /**
   * @covers ::generateRequest
   * @covers ::createUpdateRequest
   * @dataProvider withoutTaxesDataProvider
   */
  public function testCreateWithoutTaxes(array $expected) {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('2.00', 'USD'),
    ]);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'payment_gateway' => $this->gateway->id(),
    ]);
    $order->setStore($this->store);
    $order->addItem($order_item);
    $order->save();

    $sut = $this->sut->withOrder($order);
    $request = $sut->generateRequest('create');

    $this->assertTrue($request instanceof RequestInterface);
    $this->assertEquals($expected, $request->toArray());
  }

  /**
   * Data provider for testCreateWithoutTaxes().
   *
   * @return array
   *   The data.
   */
  public function withoutTaxesDataProvider() {
    return [
      [
        [
          'purchase_country' => 'US',
          'purchase_currency' => 'USD',
          'locale' => 'en-us',
          'merchant_urls' => [
            'confirmation' => 'http://localhost/checkout/1/payment/return?commerce_payment_gateway=klarna_payments',
            'notification' => 'http://localhost/payment/notify/klarna_payments?step=payment&commerce_order=1',
          ],
          'order_amount' => 200,
          'options' => NULL,
          'order_lines' => [
            [
              'quantity' => 1,
              'unit_price' => 200,
              'total_amount' => 200,
            ],
          ],
          'order_tax_amount' => 0,
        ],
      ],
    ];
  }

}
