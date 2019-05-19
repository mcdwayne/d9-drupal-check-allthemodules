<?php

namespace Drupal\Tests\uc_credit\Functional;

use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests credit card payments with the test gateway.
 *
 * This class is intended to be subclassed for use in testing other credit
 * card gateways. Subclasses which test other gateways need to:
 * - Override public static $modules, if necessary to enable the module
 *   providing the gateway and any other needed modules.
 * - Override configureGateway() to implement gateway-specific configuration.
 * No other overrides are necessary, although a subclass may want to add
 * additional test functions to cover cases not included in this base class.
 *
 * @group ubercart
 */
class CreditCardTest extends UbercartBrowserTestBase {

  /**
   * A selection of "test" numbers to use for testing credit card payments.
   *
   * These numbers all pass the Luhn algorithm check and are reserved by
   * the card issuer for testing purposes.
   *
   * @var string[]
   */
  protected static $cardTestNumbers = [
    '378282246310005',  // American Express
    '371449635398431',
    '370000000000002',
    '378734493671000',  // American Express Corporate
    '5610591081018250', // Australian BankCard
    '30569309025904',   // Diners Club
    '38520000023237',
    '38000000000006',   // Carte Blanche
    '6011111111111117', // Discover
    '6011000990139424',
    '6011000000000012',
    '3530111333300000', // JCB
    '3566002020360505',
    '3088000000000017',
    '5555555555554444', // MasterCard
    '5105105105105100',
    '4111111111111111', // Visa
    '4012888888881881',
    '4007000000027',
    '4012888818888',
  ];

  /**
   * The payment method to use.
   *
   * @var \Drupal\uc_payment\PaymentMethodInterface
   */
  protected $paymentMethod;

  public static $modules = ['uc_payment', 'uc_credit'];
  public static $adminPermissions = ['administer credit cards', 'process credit cards'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need admin permissions in order to change credit card settings.
    $this->drupalLogin($this->adminUser);

    // Configure and enable Credit card module and Test gateway.
    $this->configureCreditCard();
    $this->configureGateway();
  }

  /**
   * Tests security settings configuration.
   */
  public function testSecuritySettings() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // @todo Still need tests with existing key file
    // where key file is not readable or doesn't contain a valid key.

    // Create key directory, make it readable and writeable.
    \Drupal::service('file_system')->mkdir('sites/default/files/testkey', 0755);

    // Try to submit settings form without a key file path.
    // Save current variable, reset to its value when first installed.
    $config = \Drupal::configFactory()->getEditable('uc_credit.settings');
    $temp_variable = $config->get('encryption_path');
    $config->set('encryption_path', '')->save();

    $this->drupalGet('admin/store');
    $assert->pageTextContains('You must review your credit card security settings and enable encryption before you can accept credit card payments.');

    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      [],
      'Save configuration'
    );
    $this->assertFieldByName(
      'uc_credit_encryption_path',
      'Not configured.',
      'Key file has not yet been configured.'
    );
    // Restore variable setting.
    $config->set('encryption_path', $temp_variable)->save();

    // Try to submit settings form with an empty key file path.
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      ['uc_credit_encryption_path' => ''],
      'Save configuration'
    );
    $assert->pageTextContains('Key path must be specified in security settings tab.');

    // Specify non-existent directory.
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      ['uc_credit_encryption_path' => 'sites/default/ljkh/asdfasfaaaaa'],
      'Save configuration'
    );
    $assert->pageTextContains('You have specified a non-existent directory.');

    // Next, specify existing directory that's write protected.
    // Use /dev, as that should never be accessible.
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      ['uc_credit_encryption_path' => '/dev'],
      'Save configuration'
    );
    $assert->pageTextContains('Cannot write to directory, please verify the directory permissions.');

    // Next, specify writeable directory, but with trailing '/' and
    // excess whitespace.
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      ['uc_credit_encryption_path' => '  sites/default/files/testkey/ '],
      'Save configuration'
    );
    // See that the directory has been properly re-written to remove
    // trailing '/' and whitespace.
    $this->assertFieldByName(
      'uc_credit_encryption_path',
      'sites/default/files/testkey',
      'Key file path has been set.'
    );
    $assert->pageTextContains('Credit card encryption key file generated.');

    // Check that warning about needing key file goes away.
    $assert->pageTextNotContains('Credit card security settings must be configured in the security settings tab.');
    // Remove key file.
    \Drupal::service('file_system')->unlink('sites/default/files/testkey/' . UC_CREDIT_KEYFILE_NAME);

    // Finally, specify good directory.
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      ['uc_credit_encryption_path' => 'sites/default/files/testkey'],
      'Save configuration'
    );
    $assert->pageTextContains('Credit card encryption key file generated.');

    // Test contents - must contain 32-character hexadecimal string.
    $this->assertTrue(
      file_exists('sites/default/files/simpletest.keys/' . UC_CREDIT_KEYFILE_NAME),
      'Key has been generated and stored.'
    );
    $this->assertTrue(
      preg_match("([0-9a-fA-F]{32})", uc_credit_encryption_key()),
      'Valid key detected in key file.'
    );

    // Cleanup keys directory after test.
    \Drupal::service('file_system')->unlink('sites/default/files/testkey/' . UC_CREDIT_KEYFILE_NAME);
    \Drupal::service('file_system')->rmdir('sites/default/files/testkey');
  }

  /**
   * Tests that an order can be placed using the test gateway.
   */
  public function testCheckout() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->addToCart($this->product);

    // Submit the checkout page. Note that because of the Ajax on the country
    // fields, which is used to populate the zone select, the zone doesn't
    // actually get set by this post. That's OK because we're not checking that
    // yet. But we need to make sure that the next time we post this page
    // (which now has the country set from the first post) we include the zones.
    $edit = $this->populateCheckoutForm([
      'panes[payment][details][cc_number]' => array_rand(array_flip(self::$cardTestNumbers)),
      'panes[payment][details][cc_cvv]' => mt_rand(100, 999),
      'panes[payment][details][cc_exp_month]' => mt_rand(1, 12),
      'panes[payment][details][cc_exp_year]' => mt_rand(date('Y') + 1, 2022),
    ]);
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    // Confirm that truncated credit card number was found.
    $assert->pageTextContains('(Last 4) ' . substr($edit['panes[payment][details][cc_number]'], -4));
    // Confirm that expiry date was found.
    $assert->pageTextContains($edit['panes[payment][details][cc_exp_year]']);

    // Go back. Form will still be populated, but verify that the credit
    // card number is truncated and CVV is masked for security.
    $this->drupalPostForm(NULL, [], 'Back');
    $this->assertFieldByName('panes[payment][details][cc_number]', '(Last 4) ' . substr($edit['panes[payment][details][cc_number]'], -4), 'Truncated credit card number found.');
    $this->assertFieldByName('panes[payment][details][cc_cvv]', '---', 'Masked CVV found.');
    $this->assertFieldByName('panes[payment][details][cc_exp_month]', $edit['panes[payment][details][cc_exp_month]'], 'Expiry month found.');
    $this->assertFieldByName('panes[payment][details][cc_exp_year]', $edit['panes[payment][details][cc_exp_year]'], 'Expiry year found.');

    // Change the card number and fail with a known-bad CVV.
    $edit['panes[payment][details][cc_number]'] = array_rand(array_flip(self::$cardTestNumbers));
    $edit['panes[payment][details][cc_cvv]'] = '000';
    // If zones were set, we must re-submit them here to work around the Ajax
    // situation described above. So we just re-submit all the data to be safe.
    $this->drupalPostForm(NULL, $edit, 'Review order');
    // Confirm that truncated updated credit card number was found.
    $assert->pageTextContains('(Last 4) ' . substr($edit['panes[payment][details][cc_number]'], -4));

    // Try to submit the bad CVV.
    $this->drupalPostForm(NULL, [], 'Submit order');
    $assert->pageTextContains('We were unable to process your credit card payment. Please verify your details and try again.');

    // Go back. Again check for truncated card number and masked CVV.
    $this->drupalPostForm(NULL, [], 'Back');
    $this->assertFieldByName('panes[payment][details][cc_number]', '(Last 4) ' . substr($edit['panes[payment][details][cc_number]'], -4), 'Truncated updated credit card number found.');
    $this->assertFieldByName('panes[payment][details][cc_cvv]', '---', 'Masked CVV found.');

    // Fix the CVV and repost.
    $edit['panes[payment][details][cc_cvv]'] = mt_rand(100, 999);
    $this->drupalPostForm(NULL, $edit, 'Review order');

    // Check for success.
    $this->drupalPostForm(NULL, [], 'Submit order');
    $assert->pageTextContains('Your order is complete!');
  }

  /**
   * Tests that expiry date validation functions correctly.
   */
  public function testExpiryDate() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $order = $this->createOrder(['payment_method' => $this->paymentMethod['id']]);

    $year = date('Y');
    $month = date('n');
    for ($y = $year; $y <= $year + 2; $y++) {
      for ($m = 1; $m <= 12; $m++) {
        $edit = [
          'amount' => 1,
          'cc_data[cc_number]' => '4111111111111111',
          'cc_data[cc_cvv]' => '123',
          'cc_data[cc_exp_month]' => $m,
          'cc_data[cc_exp_year]' => $y,
        ];
        $this->drupalPostForm('admin/store/orders/' . $order->id() . '/credit/' . $this->paymentMethod['id'], $edit, 'Charge amount');

        if ($y > $year || $m >= $month) {
          // Check that expiry date in the future passed validation.
          $assert->pageTextContains('The credit card was processed successfully.');
        }
        else {
          // Check that expiry date in the past failed validation.
          $assert->pageTextNotContains('The credit card was processed successfully.');
        }
      }
    }
  }

  /**
   * Helper function to configure Credit Card payment method settings.
   */
  protected function configureCreditCard() {
    // Create key directory, make it readable and writeable.
    // Putting this under sites/default/files because SimpleTest needs to be
    // able to create the directory - this is NOT where you'd put the key file
    // on a live site. On a live site, it should be outside the web root.
    \Drupal::service('file_system')->mkdir('sites/default/files/simpletest.keys', 0755);

    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      ['uc_credit_encryption_path' => 'sites/default/files/simpletest.keys'],
      'Save configuration'
    );

    $this->assertFieldByName(
      'uc_credit_encryption_path',
      'sites/default/files/simpletest.keys',
      'Key file path has been set.'
    );

    $this->assertTrue(
      file_exists('sites/default/files/simpletest.keys/' . UC_CREDIT_KEYFILE_NAME),
      'Key has been generated and stored.'
    );
    $this->pass('Key = ' . uc_credit_encryption_key());

  }

  /**
   * Helper function to configure Credit Card gateway.
   */
  protected function configureGateway() {
    $this->paymentMethod = $this->createPaymentMethod('test_gateway');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Cleanup keys directory after test.
    \Drupal::service('file_system')->unlink('sites/default/files/simpletest.keys/' . UC_CREDIT_KEYFILE_NAME);
    \Drupal::service('file_system')->rmdir('sites/default/files/simpletest.keys');
    parent::tearDown();
  }

}
