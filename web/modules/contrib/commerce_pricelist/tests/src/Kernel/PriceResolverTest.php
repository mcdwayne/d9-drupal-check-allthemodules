<?php

namespace Drupal\Tests\commerce_pricelist\Kernel;

use Drupal\commerce\Context;
use Drupal\commerce_price\Price;
use Drupal\commerce_pricelist\Entity\PriceList;
use Drupal\commerce_pricelist\Entity\PriceListItem;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\user\Entity\Role;

/**
 * Tests the price resolver.
 *
 * @coversDefaultClass \Drupal\commerce_pricelist\PriceListPriceResolver
 * @group commerce_pricelist
 */
class PriceResolverTest extends PriceListKernelTestBase {

  /**
   * The test price list.
   *
   * @var \Drupal\commerce_pricelist\Entity\PriceList
   */
  protected $priceList;

  /**
   * The test price list item.
   *
   * @var \Drupal\commerce_pricelist\Entity\PriceListItem
   */
  protected $priceListItem;

  /**
   * The test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('8.00', 'USD'),
    ]);
    $variation->save();
    $this->variation = $this->reloadEntity($variation);

    $price_list = PriceList::create([
      'type' => 'commerce_product_variation',
      'stores' => [$this->store->id()],
      'weight' => '1',
    ]);
    $price_list->save();

    $price_list_item = PriceListItem::create([
      'type' => 'commerce_product_variation',
      'price_list_id' => $price_list->id(),
      'purchasable_entity' => $variation->id(),
      'quantity' => '1',
      'list_price' => new Price('7.70', 'USD'),
      'price' => new Price('5.00', 'USD'),
    ]);
    $price_list_item->save();

    $this->priceList = $this->reloadEntity($price_list);
    $this->priceListItem = $this->reloadEntity($price_list_item);
  }

  /**
   * Tests variation-based resolving.
   */
  public function testVariation() {
    $resolver = $this->container->get('commerce_pricelist.price_resolver');
    $other_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('8.00', 'USD'),
    ]);
    $other_variation->save();

    $context = new Context($this->user, $this->store);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEquals(new Price('5.00', 'USD'), $resolved_price);

    $resolved_price = $resolver->resolve($other_variation, 1, $context);
    $this->assertEmpty($resolved_price);
  }

  /**
   * Tests store-based resolving.
   */
  public function testStores() {
    $context = new Context($this->user, $this->store);
    $resolver = $this->container->get('commerce_pricelist.price_resolver');

    $new_store = $this->createStore();
    $this->priceList->setStores([$new_store]);
    $this->priceList->save();

    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEmpty($resolved_price);

    $context = new Context($this->user, $new_store);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEquals(new Price('5.00', 'USD'), $resolved_price);
  }

  /**
   * Tests customer-based resolving.
   */
  public function testCustomer() {
    $resolver = $this->container->get('commerce_pricelist.price_resolver');
    $customer = $this->createUser();
    $this->priceList->setCustomer($customer);
    $this->priceList->save();

    $context = new Context($this->user, $this->store);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEmpty($resolved_price);

    $context = new Context($customer, $this->store);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEquals(new Price('5.00', 'USD'), $resolved_price);
  }

  /**
   * Tests role-based resolving.
   */
  public function testCustomerRoles() {
    $resolver = $this->container->get('commerce_pricelist.price_resolver');
    $first_role = Role::create([
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ]);
    $first_role->save();
    $second_role = Role::create([
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ]);
    $second_role->save();
    $this->priceList->setCustomerRoles([$first_role->id(), $second_role->id()]);
    $this->priceList->save();

    $context = new Context($this->user, $this->store);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEmpty($resolved_price);

    $second_user = $this->createUser();
    $second_user->addRole($first_role->id());
    $second_user->save();

    $context = new Context($second_user, $this->store);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEquals(new Price('5.00', 'USD'), $resolved_price);

    $third_user = $this->createUser();
    $third_user->addRole($second_role->id());
    $third_user->save();

    $context = new Context($third_user, $this->store);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEquals(new Price('5.00', 'USD'), $resolved_price);
  }

  /**
   * Tests date-based resolving.
   */
  public function testDates() {
    $resolver = $this->container->get('commerce_pricelist.price_resolver');
    $this->priceList->setStartDate(new DrupalDateTime('-3 months'));
    $this->priceList->setEndDate(new DrupalDateTime('+1 year'));
    $this->priceList->save();

    $context = new Context($this->user, $this->store);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEquals(new Price('5.00', 'USD'), $resolved_price);

    // Set the price list to start in the future.
    $this->priceList->setStartDate(new DrupalDateTime('+1 month'));
    $this->priceList->save();

    $context = new Context($this->user, $this->store, time() + 86400);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEmpty($resolved_price);

    // Expired.
    $this->priceList->setStartDate(new DrupalDateTime('-3 months'));
    $this->priceList->setEndDate(new DrupalDateTime('-1 month'));
    $this->priceList->save();

    $context = new Context($this->user, $this->store, time() + 86400 * 2);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEmpty($resolved_price);
  }

  /**
   * Tests quantity-based resolving, along with weight and status handling.
   */
  public function testQuantity() {
    $context = new Context($this->user, $this->store);
    $resolver = $this->container->get('commerce_pricelist.price_resolver');
    $this->priceListItem->setQuantity(10);
    $this->priceListItem->save();
    // Create a second price list with a smaller weight, which should be
    // selected instead of the first price list.
    $price_list = PriceList::create([
      'type' => 'commerce_product_variation',
      'stores' => [$this->store->id()],
      'weight' => '-1',
    ]);
    $price_list->save();
    // Create two price list items, to test quantity tier selection.
    $price_list_item = PriceListItem::create([
      'type' => 'commerce_product_variation',
      'price_list_id' => $price_list->id(),
      'purchasable_entity' => $this->variation->id(),
      'quantity' => '10',
      'price' => new Price('7.00', 'USD'),
    ]);
    $price_list_item->save();
    $another_price_list_item = PriceListItem::create([
      'type' => 'commerce_product_variation',
      'price_list_id' => $price_list->id(),
      'purchasable_entity' => $this->variation->id(),
      'quantity' => '3',
      'price' => new Price('6.00', 'USD'),
    ]);
    $another_price_list_item->save();

    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEmpty($resolved_price);
    $resolved_price = $resolver->resolve($this->variation, 15, $context);
    $this->assertEquals(new Price('7.00', 'USD'), $resolved_price);

    // Reload the service to clear the static cache.
    $this->container->set('commerce_pricelist.price_resolver', NULL);
    $resolver = $this->container->get('commerce_pricelist.price_resolver');

    // Confirm that disabled price list items are skipped.
    $price_list_item->setEnabled(FALSE);
    $price_list_item->save();
    $resolved_price = $resolver->resolve($this->variation, 15, $context);
    $this->assertEquals(new Price('6.00', 'USD'), $resolved_price);

    // Reload the service to clear the static cache.
    $this->container->set('commerce_pricelist.price_resolver', NULL);
    $resolver = $this->container->get('commerce_pricelist.price_resolver');

    // Confirm that disabled price lists are skipped.
    $price_list->setEnabled(FALSE);
    $price_list->save();
    $another_user = $this->createUser();
    $context = new Context($another_user, $this->store);
    $resolved_price = $resolver->resolve($this->variation, 15, $context);
    $this->assertEquals(new Price('5.00', 'USD'), $resolved_price);
  }

  /**
   * Tests resolving list prices.
   */
  public function testListPrice() {
    $resolver = $this->container->get('commerce_pricelist.price_resolver');

    $context = new Context($this->user, $this->store);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEquals(new Price('5.00', 'USD'), $resolved_price);

    $context = new Context($this->user, $this->store, NULL, [
      'field_name' => 'list_price',
    ]);
    $resolved_price = $resolver->resolve($this->variation, 1, $context);
    $this->assertEquals(new Price('7.70', 'USD'), $resolved_price);
  }

}
