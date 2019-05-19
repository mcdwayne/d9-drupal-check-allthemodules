<?php

namespace Drupal\Tests\uc_tax\Functional;

use Drupal\node\Entity\Node;

/**
 * Tests that inclusive taxes are calculated and displayed correctly.
 *
 * @group ubercart
 */
class InclusiveTaxTest extends TaxTestBase {

  public static $modules = [
    'uc_product_kit',
    'uc_attribute',
    'uc_cart',
    'uc_payment',
    'uc_payment_pack',
    'uc_tax',
  ];

  /**
   * Test inclusive taxes with product kit attributes.
   */
  public function testProductKitAttributes() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);
    // Need a way to pay for the order that we're taxing...
    $this->createPaymentMethod('other');

    // Create a 20% inclusive tax rate.
    $rate = (object) [
      'name' => $this->randomMachineName(8),
      'rate' => 0.2,
      'taxed_product_types' => ['product'],
      'taxed_line_items' => [],
      'weight' => 0,
      'shippable' => 0,
      'display_include' => 1,
      'inclusion_text' => $this->randomMachineName(6),
    ];
    uc_tax_rate_save($rate);

    // Ensure Rules picks up the new condition.
    // entity_flush_caches();

    // Create a $10 product.
    $product = $this->createProduct(['price' => 10]);

    // Create an attribute.
    $attribute = (object) [
      'name' => $this->randomMachineName(8),
      'label' => $this->randomMachineName(8),
      'description' => $this->randomMachineName(8),
      'required' => TRUE,
      'display' => 1,
      'ordering' => 0,
    ];
    uc_attribute_save($attribute);

    // Create an option with a price adjustment of $5.
    $option = (object) [
      'aid' => $attribute->aid,
      'name' => $this->randomMachineName(8),
      'cost' => 0,
      'price' => 5,
      'weight' => 0,
      'ordering' => 0,
    ];
    uc_attribute_option_save($option);

    // Attach the attribute to the product.
    $attribute = uc_attribute_load($attribute->aid);
    uc_attribute_subject_save($attribute, 'product', $product->id(), TRUE);

    // Create a product kit containing the product.
    $kit = $this->drupalCreateNode([
      'type' => 'product_kit',
      'products' => [$product->id()],
      'default_qty' => 1,
      'mutable' => UC_PRODUCT_KIT_UNMUTABLE_WITH_LIST,
    ]);

    // Set the kit total to $9 to automatically apply a discount.
    $kit = Node::load($kit->id());
    $kit->kit_total = 9;
    $kit->save();
    $kit = Node::load($kit->id());
    $this->assertEquals($kit->products[$product->id()]->discount, -1, 'Product kit component has correct discount applied.');

    // Ensure the price is displayed tax-inclusively on the node form.
    // We expect to see $10.80 = $10.00 product - $1.00 kit discount + 20% tax.
    $this->drupalGet('node/' . $kit->id());
    $assert->pageTextContains('$10.80' . $rate->inclusion_text);
    // We expect to see $6.00 = $5.00 option adjustment + 20% tax.
    $assert->responseContains($option->name . ', +$6.00</option>');

    // Add the product kit to the cart, selecting the option.
    $attribute_key = 'products[' . $product->id() . '][attributes][' . $attribute->aid . ']';
    $this->addToCart($kit, [$attribute_key => $option->oid]);

    // Check that the subtotal is $16.80 on the cart page.
    // ($10 base + $5 option - $1 discount, with 20% tax.)
    $this->drupalGet('cart');
    $this->assertSession()->pageTextMatches('/Subtotal:\s*\$16.80/');

    // Make sure that the subtotal is also correct on the checkout page.
    $this->drupalPostForm('cart', [], 'Checkout');
    // @todo re-enable this test, see [#2306379]
    // $assert->pageTextMatches('/Subtotal:\s*\$16.80/');

    // Manually proceed to checkout review.
    $edit = $this->populateCheckoutForm();
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    $assert->responseContains('Your order is almost complete.');

    // Make sure the price is still listed tax-inclusively in cart pane on
    // the checkout page.
    // @todo This could be handled more specifically with a regex.
    // @todo re-enable this test, see [#2306379]
    // $assert->pageTextContains('$16.80' . $rate->inclusion_text);

    // Ensure the tax-inclusive price is listed on the order admin view page.
    $order_ids = \Drupal::entityQuery('uc_order')
      ->condition('delivery_first_name', $edit['panes[delivery][first_name]'])
      ->execute();
    $order_id = reset($order_ids);
    $this->assertTrue($order_id, 'Order was created successfully');
    $this->drupalGet('admin/store/orders/' . $order_id);
    // @todo re-enable this test, see [#2306379]
    // $assert->pageTextContains('$16.80' . $rate->inclusion_text);

    // And on the invoice.
    $this->drupalGet('admin/store/orders/' . $order_id . '/invoice');
    // @todo re-enable this test, see [#2306379]
    // $assert->pageTextContains('$16.80' . $rate->inclusion_text);

    // And on the printable invoice.
    $this->drupalGet('admin/store/orders/' . $order_id . '/invoice');
    // @todo re-enable this test, see [#2306379]
    // $assert->pageTextContains('$16.80' . $rate->inclusion_text);
  }

}
