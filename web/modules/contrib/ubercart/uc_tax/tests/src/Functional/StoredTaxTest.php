<?php

namespace Drupal\Tests\uc_tax\Functional;

use Drupal\uc_order\Entity\Order;

/**
 * Tests stored taxes.
 *
 * Tests that historical tax data is stored correctly, and that the
 * proper amount is displayed.
 *
 * @group ubercart
 */
class StoredTaxTest extends TaxTestBase {

  /**
   * Tests display of taxes.
   */
  public function testTaxDisplay() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    // Enable a payment method for the payment preview checkout pane.
    $this->createPaymentMethod('check');

    // Create a 20% inclusive tax rate.
    $rate = (object) [
      'name' => $this->randomMachineName(8),
      'rate' => 0.2,
      'taxed_product_types' => ['product'],
      'taxed_line_items' => [],
      'weight' => 0,
      'shippable' => 0,
      'display_include' => 1,
      'inclusion_text' => '',
    ];
    uc_tax_rate_save($rate);

    $this->drupalGet('admin/store/config/taxes');
    $assert->pageTextContains($rate->name);
    // Check that tax was saved successfully.

    // Check that Rules configuration was create for this  tax rate.
    // $this->drupalGet("admin/store/config/taxes/manage/uc_tax_$rate->id");
    // $assert->pageTextContains('Conditions');

    $this->addToCart($this->product);

    // Manually step through checkout, because $this->checkout()
    // doesn't know about taxes.
    $this->drupalPostForm('cart', [], 'Checkout');
    // Viewed cart page: Billing pane has been displayed.
    $assert->pageTextContains('Enter your billing address and information here.');
    // Viewed cart page: Tax line item displayed.
    $assert->responseContains($rate->name);
    // Viewed cart page: Correct tax amount displayed.
    $assert->responseContains(uc_currency_format($rate->rate * $this->product->price->value));

    // Submit the checkout page.
    $edit = $this->populateCheckoutForm();
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    $assert->responseContains('Your order is almost complete.');
    // Viewed checkout page: Tax line item displayed.
    $assert->responseContains($rate->name);
    // Viewed checkout page: Correct tax amount displayed.
    $assert->responseContains(uc_currency_format($rate->rate * $this->product->price->value));

    // Complete the review page.
    $this->drupalPostForm(NULL, [], 'Submit order');

    $order_ids = \Drupal::entityQuery('uc_order')
      ->condition('delivery_first_name', $edit['panes[delivery][first_name]'])
      ->execute();
    $order_id = reset($order_ids);
    if ($order_id) {
      $this->pass('Order ' . $order_id . ' has been created');

      $this->drupalGet('admin/store/orders/' . $order_id . '/edit');
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'on initial order load');

      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', [], 'Save changes');
      $assert->pageTextContains('Order changes saved.');
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'after saving order');

      // Change tax rate and ensure order doesn't change.
      $oldrate = $rate->rate;
      $rate->rate = 0.1;
      $rate = uc_tax_rate_save($rate);

      // Save order because tax changes are only updated on save.
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', [], 'Save changes');
      $assert->pageTextContains('Order changes saved.');
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $oldrate, 'after rate change');

      // Change taxable products and ensure order doesn't change.
      $class = $this->createProductClass();
      $rate->taxed_product_types = [$class->getEntityTypeId()];
      uc_tax_rate_save($rate);
      // entity_flush_caches();
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', [], 'Save changes');
      $assert->pageTextContains('Order changes saved.');
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $oldrate, 'after applicable product change');

      // Change order Status back to in_checkout and ensure tax-rate
      // changes now update the order.
      Order::load($order_id)->setStatusId('in_checkout')->save();
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', [], 'Save changes');
      $assert->pageTextContains('Order changes saved.');
      $this->assertFalse($this->loadTaxLine($order_id), 'The tax line was removed from the order when order status changed back to in_checkout.');

      // Restore taxable product and ensure new tax is added.
      $rate->taxed_product_types = ['product'];
      uc_tax_rate_save($rate);
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', [], 'Save changes');
      $assert->pageTextContains('Order changes saved.');
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'when order status changed back to in_checkout');
    }
    else {
      $this->fail('No order was created.');
    }
  }

  /**
   * Loads a tax line item from the database.
   */
  protected function loadTaxLine($order_id) {
    // Reset uc_order entity cache then load order.
    \Drupal::entityTypeManager()->getStorage('uc_order')->resetCache([$order_id]);
    $order = Order::load($order_id);
    foreach ($order->line_items as $line) {
      if ($line['type'] == 'tax') {
        return $line;
      }
    }
    return FALSE;
  }

  /**
   * Complex assert to check various parts of the tax line item.
   */
  protected function assertTaxLineCorrect($line, $rate, $when) {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->assertTrue($line, 'The tax line item was saved to the order ' . $when);
    $this->assertTrue(number_format($rate * $this->product->price->value, 2) == number_format($line['amount'], 2), 'Stored tax line item has the correct amount ' . $when);
    $this->assertFieldByName('line_items[' . $line['line_item_id'] . '][li_id]', $line['line_item_id'], 'Found the tax line item ID ' . $when);
    $assert->pageTextContains($line['title']);
    $this->pass('Found the tax title ' . $when);
    $assert->pageTextContains(uc_currency_format($line['amount']));
    $this->pass('Tax display has the correct amount ' . $when);
  }

}
