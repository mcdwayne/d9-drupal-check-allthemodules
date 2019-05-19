<?php

namespace Drupal\Tests\uc_order\Functional;

use Drupal\Core\Url;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\uc_country\Entity\Country;
use Drupal\uc_order\Entity\Order;

/**
 * Tests order administration page invoice tab functionality.
 *
 * @group ubercart
 */
class OrderInvoiceTest extends BrowserTestBase {
  use AssertMailTrait;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Don't check for or validate config schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * A user with permission to view customers.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * The user who placed the order.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $customer;

  /**
   * The order.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['uc_cart', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access user profiles',
      'view customers',
      'view all orders',
    ]);
    $this->customer = $this->drupalCreateUser();
    $country = Country::load('US');
    $this->order = Order::create([
      'uid' => $this->customer->id(),
      'billing_country' => $country->id(),
      'billing_zone' => 'AK',
    ]);
    $this->order->save();

    // Ensure test mails are logged.
    \Drupal::configFactory()->getEditable('system.mail')
      ->set('interface.uc_order', 'test_html_mail_collector')
      ->save();
  }

  /**
   * Tests order invoice tab view invoice.
   */
  public function testViewOrderInvoice() {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Check to make sure the page can be viewed without error.
    $this->drupalGet('admin/store/orders/' . $this->order->id() . '/invoice');
    $assert->statusCodeEquals(200);

    // Check for some elements of invoice content to ensure it rendered.
    $assert->linkByHrefExists('user/' . $this->customer->id() . '/orders/' . $this->order->id());
    $assert->pageTextContains($this->order->getEmail());
    $assert->pageTextContains($this->order->getAddress('billing')->getZone());
  }

  /**
   * Tests order invoice tab print invoice.
   */
  public function testPrintOrderInvoice() {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Check to make sure the page can be viewed without error.
    $this->drupalGet('admin/store/orders/' . $this->order->id() . '/invoice/print');
    $assert->statusCodeEquals(200);

    // Check for 'Print invoice' and 'Back' buttons.
    $assert->buttonExists('Print invoice');
    $assert->buttonExists('Back');

    // Check for some elements of invoice content to ensure it rendered.
    $assert->linkByHrefExists('user/' . $this->customer->id() . '/orders/' . $this->order->id());
    $assert->pageTextContains($this->order->getEmail());
    $assert->pageTextContains($this->order->getAddress('billing')->getZone());
  }

  /**
   * Tests order invoice tab mail invoice.
   */
  public function testMailOrderInvoice() {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Check to make sure the page can be viewed without error.
    $this->drupalGet('admin/store/orders/' . $this->order->id() . '/invoice/mail');
    $assert->statusCodeEquals(200);

    // Check textfield 'email' exists and has customer e-mail filled in.
    $assert->fieldValueEquals('email', $this->order->getEmail());
    // Check button 'Mail invoice' exists and press it - this will send an
    // email using the test_html_mail_collector so we can examine it later.
    $assert->buttonExists('Mail invoice')->press();

    // Examine the collected email and check some of the contents.
    $this->assertMailString('subject', 'Your Order Invoice', 1, 'Order invoice was sent via email');
    $this->assertMailString('body', '<b>Billing Address:</b><br />', 1, 'Markup found in invoice mail.');
    // Make sure the logo image uses an absolute URL.
    // @see https://www.drupal.org/project/drupal/issues/2704597
    $uri = theme_get_setting('logo.url');
    $expected = Url::fromUserInput($uri, ['absolute' => TRUE])->toString();
    $this->assertMailString('body', $expected, 1, 'Logo image has absolute URL.');
  }

}
