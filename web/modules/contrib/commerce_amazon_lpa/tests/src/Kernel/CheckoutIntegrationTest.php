<?php

namespace Drupal\Tests\commerce_amazon_lpa\Kernel;

use Drupal\commerce_checkout\Entity\CheckoutFlow;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests integrations with commerce_checkout.
 *
 * @group commerce_amazon_lpa
 */
class CheckoutIntegrationTest extends CommerceKernelTestBase {

  public static $modules = [
    'path',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_payment',
    'commerce_checkout',
    'commerce_product',
    'commerce_amazon_lpa',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_order');
    $this->installConfig([
      'commerce_product',
      'commerce_order',
      'commerce_checkout',
      'commerce_amazon_lpa',
    ]);
  }

  /**
   * Tests the checkout flow.
   */
  public function testCheckoutFlowExists() {
    $checkout_flow = CheckoutFlow::load('amazon_pay');
    $this->assertInstanceOf(CheckoutFlow::class, $checkout_flow);

    $user = $this->createUser(['administer commerce_checkout_flow']);
    $this->assertTrue($checkout_flow->access('update', $user));
    $this->assertFalse($checkout_flow->access('delete', $user));
  }

  /**
   * Tests the Amazon Pay checkout flow resolver.
   */
  public function testCheckoutFlowResolver() {
    $this->config('commerce_amazon_lpa.settings')
      ->set('mode', 'test')
      ->save();

    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->bundle()->willReturn('default');
    $order->hasField('amazon_order_reference')->willReturn(FALSE);

    $checkout_flow = $this->container->get('commerce_checkout.chain_checkout_flow_resolver')->resolve($order->reveal());
    $this->assertEquals('default', $checkout_flow->id());

    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->hasField('amazon_order_reference')->willReturn(TRUE);
    $order->get('amazon_order_reference')->willReturn((object) [
      'value' => 'S01-6615956-0410197',
    ]);

    $checkout_flow = $this->container->get('commerce_checkout.chain_checkout_flow_resolver')->resolve($order->reveal());
    $this->assertEquals('amazon_pay', $checkout_flow->id());
  }

}
