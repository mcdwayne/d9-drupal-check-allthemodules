<?php

namespace Drupal\Tests\commerce_amazon_lpa\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests integrations with commerce_order.
 *
 * @group commerce_amazon_lpa
 */
class OrderIntegrationTest extends CommerceKernelTestBase {

  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_payment',
    'commerce_checkout',
    'commerce_amazon_lpa',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_order');
    $this->installConfig(['commerce_order', 'commerce_amazon_lpa']);
  }

  /**
   * Tests that the order reference base field exists and can be used.
   */
  public function testAmazonOrderReferenceIdBaseField() {
    $order = Order::create([
      'type' => 'default',
    ]);
    $this->assertTrue($order->hasField('amazon_order_reference'));
    $order->get('amazon_order_reference')->setValue('S01-6615956-0410197');
    $order->save();
    $order = $this->reloadEntity($order);
    $this->assertEquals('S01-6615956-0410197', $order->get('amazon_order_reference')->value);
  }

  /**
   * Test the Amazon condition.
   */
  public function testAmazonOrderCondition() {
    $condition_manager = $this->container->get('plugin.manager.commerce_condition');

    $this->config('commerce_amazon_lpa.settings')
      ->set('mode', 'test')
      ->save();
    /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
    $condition = $condition_manager->createInstance('amazon_order', []);

    $order = Order::create([
      'type' => 'default',
    ]);

    $this->assertFalse($condition->evaluate($order));
    $order->get('amazon_order_reference')->setValue('S01-6615956-0410197');
    $this->assertTrue($condition->evaluate($order));
    $order->get('amazon_order_reference')->setValue('P01-6615956-0410197');
    $this->assertFalse($condition->evaluate($order));

    // Change to prod, reload condition.
    $this->config('commerce_amazon_lpa.settings')
      ->set('mode', 'live')
      ->save();
    /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
    $condition = $condition_manager->createInstance('amazon_order', []);
    $this->assertTrue($condition->evaluate($order));
  }

}
