<?php

namespace Drupal\Tests\braintree_cashier\FunctionalJavascript;

use Drupal\braintree_cashier\Entity\BraintreeCashierDiscount;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests discounts.
 */
class CouponTest extends WebDriverTestBase {

  use BraintreeCashierTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['braintree_cashier'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The account created for the test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupBraintreeApi();
    $billing_plan = $this->createMonthlyBillingPlan();
    BraintreeCashierDiscount::create([
      'billing_plan' => [$billing_plan->id()],
      'name' => 'CI Coupon',
      'discount_id' => 'CI_COUPON',
      'environment' => 'sandbox',
      'status' => TRUE,
    ])->save();

    $this->account = $this->drupalCreateUser();
    $this->drupalLogin($this->account);
    $this->drupalGet(Url::fromRoute('braintree_cashier.signup_form'));
  }

  /**
   * Tests the confirm coupon button result text.
   */
  public function testCouponConfirmation() {
    $page = $this->getSession()->getPage();
    $page->fillField('coupon_code', 'CI_COUPON');
    $page->pressButton('Confirm coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Success! The coupon code CI_COUPON gives a discount of $3.00 each month');
  }

  /**
   * Tests that the coupon actually reduces the invoice amount for the plan.
   */
  public function testCouponAppliedOnSignup() {
    $this->fillInCardForm($this, [
      'card_number' => '4242424242424242',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);

    $this->testCouponConfirmation();

    $this->getSession()->getPage()->find('css', '#submit-button')->click();
    $this->assertSession()->waitForElementVisible('css', '.messages--status', 20000);
    $this->assertSession()->pageTextContains('You have been signed up for the CI Monthly plan. Thank you, and enjoy your subscription!');
    $this->drupalGet(Url::fromRoute('braintree_cashier.invoices', [
      'user' => $this->account->id(),
    ]));
    // The discount is $3.00 and the plan is $12.00.
    $this->assertSession()->elementTextContains('css', '.payment-history', '$9.00');
    $this->assertSession()->elementTextContains('css', '.upcoming-invoice', '$9.00');
  }

  /**
   * Tests that the coupon reduces the invoice amount.
   *
   * The coupon is applied to a subscription started from the my subscription
   * tab.
   */
  public function testCouponAppliedOnMySubscriptionTab() {
    $this->drupalGet(Url::fromRoute('braintree_cashier.payment_method', [
      'user' => $this->account->id(),
    ]));
    $this->assertSession()->waitForElementVisible('css', '.braintree-loaded');
    $this->createScreenshot('/tmp/screenshot.jpg');
    $this->fillInCardForm($this, [
      'card_number' => '4242424242424242',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);
    $this->getSession()->getPage()->pressButton('Add payment method');
    $this->assertSession()->waitForElementVisible('css', '.messages--status', 20000);
    $this->assertSession()->pageTextContains('Your payment method has been updated successfully!');
    $this->drupalGet(Url::fromRoute('braintree_cashier.my_subscription', [
      'user' => $this->account->id(),
    ]));
    $this->getSession()->getPage()->fillField('coupon_code', 'CI_COUPON');
    $this->getSession()->getPage()->pressButton('Sign up!');

    $this->getSession()->getPage()->pressButton('Confirm');

    $this->assertSession()->waitForElementVisible('css', '.messages--status', 20000);
    $this->assertSession()->pageTextContains('Your subscription has been updated!');
    $this->drupalGet(Url::fromRoute('braintree_cashier.invoices', [
      'user' => $this->account->id(),
    ]));
    // The discount is $3.00 and the plan is $12.00.
    $this->assertSession()->elementTextContains('css', '.payment-history', '$9.00');
    $this->assertSession()->elementTextContains('css', '.upcoming-invoice', '$9.00');
  }

}
