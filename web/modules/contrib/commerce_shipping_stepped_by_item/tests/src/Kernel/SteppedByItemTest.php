<?php

namespace Drupal\Tests\commerce_shipping_stepped_by_item\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_shipping\Entity\Shipment;
use Drupal\commerce_shipping\Entity\ShippingMethod;
use Drupal\commerce_shipping\ProposedShipment;
use Drupal\commerce_shipping\ShipmentItem;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\physical\Weight;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the stepped_by_item shipping method plugin.
 *
 * @group commerce_shipping_stepped_by_item
 */
class SteppedByItemTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'physical',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_product',
    'commerce_shipping',
    'commerce_shipping_stepped_by_item',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_shipping_method');
    $this->installEntitySchema('commerce_shipment');
    $this->installConfig([
      'physical',
      'profile',
      'commerce_order',
      'commerce_shipping',
    ]);
  }

  /**
   * Tests the stepped_by_item plugin.
   *
   * @dataProvider testSteppedByItemProvider
   */
  public function testSteppedByItem($item_quantities, $expected_price_number) {
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'store_id' => $this->store->id(),
    ]);
    $order->save();
    $order = $this->reloadEntity($order);

    /** @var \Drupal\commerce_shipping\Entity\ShippingMethodInterface $shipping_method */
    $shipping_method = ShippingMethod::create([
      'name' => $this->randomString(),
      'status' => 1,
      'plugin' => [
        'target_plugin_id' => 'stepped_by_item',
        'target_plugin_configuration' => [
          'rate_label' => 'Stepped',
          'rate_map' => [
            [
              'quantity' => 10,
              'amount' => [
                'number' => 10,
                'currency_code' => 'GBP',
              ],
            ],
            [
              'quantity' => 50,
              'amount' => [
                'number' => 20,
                'currency_code' => 'GBP',
              ],
            ],
            [
              'quantity' => 100,
              'amount' => [
                'number' => 30,
                'currency_code' => 'GBP',
              ],
            ],
          ],
        ],
      ],
    ]);
    $shipping_method->save();
    $shipping_method = $this->reloadEntity($shipping_method);

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = Profile::create([
      'type' => 'customer',
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    $shipment = Shipment::create([
      'type' => 'default',
      'state' => 'ready',
      'order_id' => $order->id(),
    ]);

    $this->assertEquals($order, $shipment->getOrder());
    $this->assertEquals($order->id(), $shipment->getOrderId());

    $package_type_manager = \Drupal::service('plugin.manager.commerce_package_type');
    $package_type = $package_type_manager->createInstance('custom_box');
    $shipment->setPackageType($package_type);
    $this->assertEquals($package_type, $shipment->getPackageType());

    $shipment->setShippingMethod($shipping_method);
    $this->assertEquals($shipping_method, $shipment->getShippingMethod());
    $this->assertEquals($shipping_method->id(), $shipment->getShippingMethodId());

    $shipping_service = $this->randomString();
    $shipment->setShippingService($shipping_service);
    $this->assertEquals($shipping_service, $shipment->getShippingService());

    $shipment->setShippingProfile($profile);
    $this->assertEquals($profile, $shipment->getShippingProfile());

    $shipment->setTitle('Shipment #1');
    $this->assertEquals('Shipment #1', $shipment->getTitle());

    $shipping_method_plugin = $shipping_method->getPlugin();

    $items = [];
    $items[] = new ShipmentItem([
      'order_item_id' => 10,
      'title' => 'T-shirt (red, large)',
      'quantity' => $item_quantities[0],
      'weight' => new Weight('40', 'kg'),
      'declared_value' => new Price('30', 'GBP'),
    ]);
    $items[] = new ShipmentItem([
      'order_item_id' => 10,
      'title' => 'T-shirt (blue, large)',
      'quantity' => $item_quantities[1],
      'weight' => new Weight('30', 'kg'),
      'declared_value' => new Price('30', 'GBP'),
    ]);
    $shipment->setItems($items);

    $shipping_rates = $shipping_method_plugin->calculateRates($shipment);

    $this->assertCount(1, $shipping_rates, "A single shipping rate is returned.");
    $shipping_rate = array_pop($shipping_rates);

    $this->assertEquals(new Price($expected_price_number, 'GBP'), $shipping_rate->getAmount(), "The shipping rate has the expected price.");
  }

  /**
   * Data provider for testSteppedByItem().
   */
  public function testSteppedByItemProvider() {
    return [
      'fewer than 10 items' => [
        [2, 2],
        10,
      ],
      '10 items' => [
        [5, 5],
        10,
      ],
      'between 10 and 50 items' => [
        [10, 5],
        20,
      ],
      '50 items' => [
        [25, 25],
        20,
      ],
      'between 50 and 100 items' => [
        [50, 5],
        30,
      ],
      '100 items' => [
        [50, 50],
        30,
      ],
      'over 100 items' => [
        [100, 5],
        30,
      ],
    ];
  }

}
