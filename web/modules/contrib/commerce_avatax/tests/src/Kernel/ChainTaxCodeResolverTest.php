<?php

namespace Drupal\Tests\commerce_avatax\Kernel;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the chain tax code resolver.
 *
 * @group commerce_avatax
 */
class ChainTaxCodeResolverTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_tax',
    'commerce_avatax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('commerce_product');

    // Turn off title generation to allow explicit values to be used.
    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();
  }

  /**
   * Tests the product variation tax code resolver.
   */
  public function testProductVariationTaxCodeResolver() {
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
      'avatax_tax_code' => 'TESTCODE123',
    ]);
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'purchased_entity' => $variation1,
    ]);

    $resolved_code = $this->container->get('commerce_avatax.chain_tax_code_resolver')->resolve($order_item);
    $this->assertEquals('TESTCODE123', $resolved_code);


    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'purchased_entity' => $variation2,
    ]);
    $resolved_code = $this->container->get('commerce_avatax.chain_tax_code_resolver')->resolve($order_item);
    $this->assertEquals(NULL, $resolved_code);
  }

  /**
   * Tests resolving a generic purchasable entity.
   *
   * There should be nothing resolved, which allows Avalara to use the system
   * default.
   */
  public function testNonProductVariationTaxCodeResolver() {
    $purchased_entity = $this->prophesize(PurchasableEntityInterface::class);
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getPurchasedEntity()->willReturn($purchased_entity->reveal());
    $resolved_code = $this->container->get('commerce_avatax.chain_tax_code_resolver')->resolve($order_item->reveal());
    $this->assertEquals(NULL, $resolved_code);
  }

}
