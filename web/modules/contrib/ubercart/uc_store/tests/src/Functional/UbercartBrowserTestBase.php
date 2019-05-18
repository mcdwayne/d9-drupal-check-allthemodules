<?php

namespace Drupal\Tests\uc_store\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\uc_country\Entity\Country;
use Drupal\uc_order\Entity\Order;
use Drupal\Tests\uc_attribute\Traits\AttributeTestTrait;
use Drupal\Tests\uc_order\Traits\OrderTestTrait;

/**
 * Base class for Ubercart PHPUnit browser tests.
 */
abstract class UbercartBrowserTestBase extends BrowserTestBase {
  use AssertMailTrait;
  use AttributeTestTrait;
  use OrderTestTrait;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * The standard modules to be loaded for all tests.
   *
   * @var string[]
   */
  public static $modules = ['block', 'uc_cart'];

  /**
   * Don't check for or validate config schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * User with privileges to do everything.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Permissions for administrator user.
   *
   * @var string[]
   */
  public static $adminPermissions = [
    'access administration pages',
    'administer store',
    'administer countries',
    'administer order workflow',
    'administer product classes',
    'administer product features',
    'administer products',
    'administer content types',
    'create product content',
    'delete any product content',
    'edit any product content',
    'create orders',
    'view all orders',
    'edit orders',
    'delete orders',
    'unconditionally delete orders',
  ];

  /**
   * Test product.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Place the tabs and actions blocks as various tests use them.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Collect admin permissions.
    $class = get_class($this);
    $adminPermissions = [];
    while ($class) {
      if (property_exists($class, 'adminPermissions')) {
        $adminPermissions = array_merge($adminPermissions, $class::$adminPermissions);
      }
      $class = get_parent_class($class);
    }

    // Enable a random selection of 8 countries so we're not always
    // testing with the 1 site default.
    $countries = \Drupal::service('country_manager')->getAvailableList();
    $country_ids = array_rand($countries, 8);
    foreach ($country_ids as $country_id) {
      // Don't use the country UI, we're not testing that here...
      Country::load($country_id)->enable()->save();
    }
    // Last one of the 8 gets to be the store default country.
    \Drupal::configFactory()->getEditable('uc_store.settings')->set('address.country', $country_id)->save();

    // Create a store administrator user account.
    $this->adminUser = $this->drupalCreateUser($adminPermissions);

    // Create a test product.
    $this->product = $this->createProduct(['uid' => $this->adminUser->id(), 'promote' => 0]);
  }

  /**
   * Adds a product to the cart.
   */
  protected function addToCart($product, array $options = []) {
    $this->drupalPostForm('node/' . $product->id(), $options, 'Add to cart');
  }

  /**
   * Helper function to fill-in required fields on the checkout page.
   *
   * @param array $edit
   *   The form-values array to which to add required fields.
   *
   * @return array
   *   The values array ready to pass to the checkout page.
   */
  protected function populateCheckoutForm(array $edit = []) {
    foreach (['billing', 'delivery'] as $pane) {
      $prefix = 'panes[' . $pane . ']';
      $key = $prefix . '[country]';
      $country_id = isset($edit[$key]) ? $edit[$key] : \Drupal::config('uc_store.settings')->get('address.country');
      $country = \Drupal::service('country_manager')->getCountry($country_id);

      $edit += [
        $prefix . '[first_name]' => $this->randomMachineName(10),
        $prefix . '[last_name]' => $this->randomMachineName(10),
        $prefix . '[street1]' => $this->randomMachineName(10),
        $prefix . '[city]' => $this->randomMachineName(10),
        $prefix . '[postal_code]' => (string) mt_rand(10000, 99999),
        $prefix . '[country]' => $country_id,
      ];

      // Don't try to set the zone unless the store country has zones!
      if (!empty($country->getZones())) {
        $edit += [
          $prefix . '[zone]' => array_rand($country->getZones()),
        ];
      }
    }

    // If the email address has not been set, and the user has not logged in,
    // add a primary email address.
    if (!isset($edit['panes[customer][primary_email]']) && !$this->loggedInUser) {
      $edit['panes[customer][primary_email]'] = $this->randomMachineName(8) . '@example.com';
    }

    return $edit;
  }

  /**
   * Executes the checkout process.
   *
   * @return \Drupal\uc_order\Entity\Order|false
   *   The created order, or FALSE if the order could not be created.
   */
  protected function checkout(array $edit = []) {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalPostForm('cart', [], 'Checkout');
    // Check for billing pane text on cart page.
    $assert->pageTextContains('Enter your billing address and information here.');

    $edit = $this->populateCheckoutForm($edit);

    // Submit the checkout page.
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    $assert->pageTextContains('Your order is almost complete.');

    // Complete the review page.
    $this->drupalPostForm(NULL, [], 'Submit order');

    $order_ids = \Drupal::entityQuery('uc_order')
      ->condition('billing_first_name', $edit['panes[billing][first_name]'])
      ->execute();
    $order_id = reset($order_ids);
    if ($order_id) {
      $this->pass(format_string('Order %order_id has been created', ['%order_id' => $order_id]));
      $order = Order::load($order_id);
    }
    else {
      $this->fail('No order was created.');
      $order = FALSE;
    }

    return $order;
  }

  /**
   * Defines a new payment method.
   *
   * @param string $plugin_id
   *   The plugin ID of the method.
   * @param array $values
   *   (optional) An associative array with possible keys of 'id', and 'label',
   *   to initialize the payment method.
   *
   * @return array
   *   Array with keys 'id' and 'value', holding the machine name and label of
   *   the created payment method.
   */
  protected function createPaymentMethod($plugin_id, array $values = []) {
    $has_user = $this->loggedInUser;
    if (!$has_user) {
      $this->drupalLogin($this->adminUser);
    }

    $values += [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ];
    $this->drupalPostForm('admin/store/config/payment/add/' . $plugin_id, $values, 'Save');

    if (!$has_user) {
      $this->drupalLogout();
    }

    return $values;
  }

  /**
   * Asserts that the most recently sent e-mails do not have the string in it.
   *
   * @param string $field_name
   *   Name of field or message property to assert: subject, body, id, ...
   * @param string $string
   *   String to search for.
   * @param int $email_depth
   *   Number of emails to search for string, starting with most recent.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message
   *   text, not t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoMailString($field_name, $string, $email_depth, $message = '', $group = 'Other') {
    $mails = $this->getMails();
    $string_found = FALSE;
    for ($i = count($mails) - 1; $i >= count($mails) - $email_depth && $i >= 0; $i--) {
      $mail = $mails[$i];
      // Normalize whitespace, as we don't know what the mail system might have
      // done. Any run of whitespace becomes a single space.
      $normalized_mail = preg_replace('/\s+/', ' ', $mail[$field_name]);
      $normalized_string = preg_replace('/\s+/', ' ', $string);
      $string_found = (FALSE !== strpos($normalized_mail, $normalized_string));
      if ($string_found) {
        break;
      }
    }
    if (!$message) {
      $message = format_string('Expected text not found in @field of email message: "@expected".', ['@field' => $field_name, '@expected' => $string]);
    }
    return $this->assertFalse($string_found, $message, $group);
  }

}
