<?php

namespace Drupal\Tests\sendwithus_commerce\Kernel;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\sendwithus\Context;
use Drupal\sendwithus\Template;
use Drupal\sendwithus_commerce\Resolver\Variable\OrderVariableCollector;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Variable collection kernel tests.
 *
 * @group sendwithus_commerce
 * @coversDefaultClass \Drupal\sendwithus_commerce\Resolver\Variable\OrderVariableCollector
 */
class VariableCollectorTest extends CommerceKernelTestBase {

  public static $modules = [
    'profile',
    'state_machine',
    'entity_reference_revisions',
    'path',
    'commerce_order',
    'commerce_product',
    'commerce_price',
    'sendwithus_commerce',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig('path');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');

    $account = $this->createUser(['name' => 'test']);

    \Drupal::currentUser()->setAccount($account);
  }

  /**
   * @covers ::collect
   */
  public function testDefault() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'test_product',
      'title' => 'Test title',
    ]);
    $variation->setPrice(new Price('123', 'USD'));
    $variation->save();

    $product = Product::create([
      'type' => 'default',
      'title' => 'Test product',
    ]);
    $product->addVariation($variation)
      ->save();

    $adjustments = [
      new Adjustment([
        'type' => 'promotion',
        'label' => 'Promotion 1',
        'amount' => new Price('-10', 'USD'),
        'locked' => TRUE,
      ]),
      new Adjustment([
        'type' => 'promotion',
        'label' => 'Promotion 2',
        'amount' => new Price('-6.15', 'USD'),
        'percentage' => '0.05',
        'locked' => TRUE,
      ]),
    ];

    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'mail' => 'admin@example.com',
    ]);
    foreach ($adjustments as $index => $adjustment) {
      $orderItem = OrderItem::create([
        'type' => 'default',
        'purchased_entity' => $variation,
        'title' => 'Title ' . $index,
      ]);
      $orderItem->setUnitPrice($variation->getPrice());
      $orderItem->addAdjustment($adjustment);
      $orderItem->save();

      $order->addItem($orderItem);
    }
    $order->save();

    $template = new Template('1234');
    $context = new Context('modulename', 'test', new ParameterBag([
      'params' => ['order' => $order],
    ]));

    $sut = new OrderVariableCollector($this->container->get('commerce_order.order_total_summary'));
    $sut->collect($template, $context);

    $expected = [
      'id' => '1',
      'mail' => 'admin@example.com',
      'type' => 'default',
      'customer' => [
        'id' => '1',
        'name' => 'test',
        'ip' => '127.0.0.1',
      ],
      'order_number' => NULL,
      'store' => [
        'id' => '1',
        'label' => $this->store->label(),
        'type' => 'online',
      ],
      'adjustments' => [],
      'items' => [
        [
          'id' => '1',
          'label' => 'Test product',
          'quantity' => 1,
          'unit_price' => [
            'number' => 123,
            'currency_code' => 'USD',
          ],
          'is_unit_price_overridden' => '0',
          'adjusted_unit_price' => [
            'number' => 113,
            'currency_code' => 'USD',
          ],
          'adjustments' => [
            [
              'type' => 'promotion',
              'amount' => [
                'number' => -10,
                'currency_code' => 'USD',
              ],
              'percentage' => NULL,
              'label' => 'Promotion 1',
            ],
          ],
          'total_price' => [
            'number' => 123,
            'currency_code' => 'USD',
          ],
          'adjusted_total_price' => [
            'number' => 113,
            'currency_code' => 'USD',
          ],
          'created' => $order->getItems()[0]->getCreatedTime(),
          'purchased_entity' => [
            'id' => '1',
            'label' => 'Test product',
            'type' => 'default',
            'price' => [
              'number' => 123,
              'currency_code' => 'USD',
            ],
          ],
        ],
        [
          'id' => '2',
          'label' => 'Test product',
          'quantity' => 1,
          'unit_price' => [
            'number' => 123,
            'currency_code' => 'USD',
          ],
          'is_unit_price_overridden' => '0',
          'adjusted_unit_price' => [
            'number' => 116.85,
            'currency_code' => 'USD',
          ],
          'adjustments' => [
            [
              'type' => 'promotion',
              'amount' => [
                'number' => -6.15,
                'currency_code' => 'USD',
              ],
              'percentage' => 5,
              'label' => 'Promotion 2',
            ],
          ],
          'total_price' => [
            'number' => 123,
            'currency_code' => 'USD',
          ],
          'adjusted_total_price' => [
            'number' => 116.85,
            'currency_code' => 'USD',
          ],
          'created' => $order->getItems()[1]->getCreatedTime(),
          'purchased_entity' => [
            'id' => '1',
            'label' => 'Test product',
            'type' => 'default',
            'price' => [
              'number' => 123,
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
      'is_locked' => FALSE,
      'created' => $order->getCreatedTime(),
      'placed' => NULL,
      'completed' => NULL,
      'state' => [
        'label' => 'Draft',
        'value' => 'draft',
      ],
      'payment_method' => [],
      'payment_gateway' => [],
      'totals' => [
        'subtotal' => [
          'number' => 246,
          'currency_code' => 'USD',
        ],
        'total' => [
          'number' => 229.85,
          'currency_code' => 'USD',
        ],
        'adjustments' => [
          [
            'label' => 'Promotion 1',
            'amount' => [
              'number' => -10,
              'currency_code' => 'USD',
            ],
            'percentage' => NULL,
            'source_id' => NULL,
            'included' => FALSE,
            'locked' => TRUE,
            'total' => [
              'number' => -10,
              'currency_code' => 'USD',
            ],
            'type' => 'promotion',
          ],
          [
            'label' => 'Promotion 2',
            'amount' => [
              'number' => -6.15,
              'currency_code' => 'USD',
            ],
            'percentage' => 5,
            'source_id' => NULL,
            'included' => FALSE,
            'locked' => TRUE,
            'total' => [
              'number' => -6.15,
              'currency_code' => 'USD',
            ],
            'type' => 'promotion',
          ],
        ],
      ],
    ];

    $this->assertEquals($expected, $template->getVariable('template_data')['order']);
  }

}
