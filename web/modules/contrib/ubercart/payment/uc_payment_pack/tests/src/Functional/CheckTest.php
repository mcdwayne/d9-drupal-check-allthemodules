<?php

namespace Drupal\Tests\uc_payment_pack\Functional;

use Drupal\Tests\uc_store\Traits\AddressTestTrait;
use Drupal\uc_order\Entity\Order;

/**
 * Tests the payment method pack Check payment method.
 *
 * @group ubercart
 */
class CheckTest extends PaymentPackTestBase {
  use AddressTestTrait;

  /**
   * Tests for Check payment method.
   */
  public function testCheck() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalGet('admin/store/config/payment/add/check');
    $assert->pageTextContains('Check');
    $this->assertFieldByName('settings[policy]', 'Personal and business checks will be held for up to 10 business days to ensure payment clears before an order is shipped.', 'Default check payment policy found.');

    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'settings[policy]' => $this->randomString(),
    ];

    // Fill in and save the check address settings.
    $address = $this->createAddress();
    // We don't use the last_name field that was randomly generated.
    $address->setLastName('');

    // Post the country choice, which will reload the page with the
    // country-specific zone selection.
    $this->drupalPostForm(NULL, ['settings[address][country]' => $address->getCountry()], 'Save');

    // Don't try to set the zone unless the chosen country has zones!
    if (!empty($address->getZone())) {
      $edit += ['settings[address][zone]' => $address->getZone()];
    }

    // Fill in the rest of the form fields and post.
    $edit += [
      'settings[name]' => $address->getFirstName(),
      'settings[address][company]' => $address->getCompany(),
      'settings[address][street1]' => $address->getStreet1(),
      'settings[address][street2]' => $address->getStreet2(),
      'settings[address][city]' => $address->getCity(),
      'settings[address][postal_code]' => $address->getPostalCode(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Test that check settings show up on checkout page.
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', $edit['id'], 'Check payment method is selected at checkout.');
    $assert->pageTextContains('Checks should be made out to:');
    // Test for properly formatted check mailing address.
    $assert->responseContains((string) $address);
    $assert->assertEscaped($edit['settings[policy]'], 'Check payment policy found at checkout.');

    // Test that check settings show up on the review order page.
    $this->drupalPostForm(NULL, [], 'Review order');
    // Test that Check payment method was found on the review page.
    $assert->pageTextContains('Check');
    // Test that Check payment method help text was found on the review page.
    $assert->pageTextContains('Mail to');
    // Test for properly formatted check mailing address.
    $assert->responseContains((string) $address);
    $this->drupalPostForm(NULL, [], 'Submit order');

    // Test user order view.
    $order = Order::load(1);
    $this->assertEquals($order->getPaymentMethodId(), $edit['id'], 'Order has check payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    // Test that Check payment method is displayed on user orders page.
    $assert->pageTextContains('Method: Check');

    // Test admin order view - receive check.
    $this->drupalGet('admin/store/orders/' . $order->id());
    // Test that Check payment method is displayed on admin orders page.
    $assert->pageTextContains('Method: Check');
    $assert->linkExists('Receive Check');
    $this->clickLink('Receive Check');
    $this->assertFieldByName('amount', number_format($order->getTotal(), 2, '.', ''), 'Amount field defaults to order total.');

    // Random receive date between tomorrow and 1 year from now.
    $receive_date = strtotime('now +' . mt_rand(1, 365) . ' days');
    $formatted = \Drupal::service('date.formatter')->format($receive_date, 'uc_store');

    $edit = [
      'comment' => $this->randomString(),
      'clear_date[date]' => date('Y-m-d', $receive_date),
    ];
    $this->drupalPostForm(NULL, $edit, 'Receive check');
    $assert->linkNotExists('Receive Check');
    // Test that expected Check clear data is found.
    $assert->pageTextContains('Clear Date: ' . $formatted);

    // Test that user order view shows check received.
    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $assert->pageTextContains('Check received');
    $assert->pageTextContains('Expected clear date:');
    $assert->pageTextContains($formatted, 'Check clear date found.');
  }

}
