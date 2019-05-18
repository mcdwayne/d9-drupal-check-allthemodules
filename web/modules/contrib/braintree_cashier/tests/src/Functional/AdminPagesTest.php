<?php

namespace Drupal\Tests\braintree_cashier\Functional;

use Drupal\braintree_cashier\Entity\BraintreeCashierDiscount;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscription;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\Core\Url;
use Drupal\Tests\braintree_cashier\FunctionalJavascript\BraintreeCashierTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests braintree cashier admin pages.
 */
class AdminPagesTest extends BrowserTestBase {

  use BraintreeCashierTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['braintree_cashier'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $billing_plan = $this->createMonthlyBillingPlan();
    $account = $this->createUser([], NULL, TRUE);
    BraintreeCashierSubscription::create([
      'subscription_type' => $billing_plan->getSubscriptionType(),
      'subscribed_user' => $account->id(),
      'status' => BraintreeCashierSubscriptionInterface::ACTIVE,
      'name' => $billing_plan->getName(),
      'billing_plan' => $billing_plan->id(),
      'roles_to_assign' => $billing_plan->getRolesToAssign(),
      'roles_to_revoke' => $billing_plan->getRolesToRevoke(),
      'period_end_date' => time() + 10000,
      'braintree_subscription_id' => '123',
    ])->save();
    BraintreeCashierSubscription::create([
      'subscription_type' => $billing_plan->getSubscriptionType(),
      'subscribed_user' => $account->id(),
      'status' => BraintreeCashierSubscriptionInterface::CANCELED,
      'name' => $billing_plan->getName(),
      'billing_plan' => $billing_plan->id(),
      'roles_to_assign' => $billing_plan->getRolesToAssign(),
      'roles_to_revoke' => $billing_plan->getRolesToRevoke(),
      'period_end_date' => time() + 10000,
      'braintree_subscription_id' => '123',
    ])->save();
    BraintreeCashierDiscount::create([
      'billing_plan' => [$billing_plan->id()],
      'name' => 'CI Coupon',
      'discount_id' => 'CI_COUPON',
      'environment' => 'sandbox',
      'status' => TRUE,
    ])->save();
    $this->drupalLogin($account);
  }

  /**
   * Tests that the View for administering subscriptions exists.
   *
   * Tests that the View overrides the entity collection route.
   */
  public function testSubscriptionCollectionExists() {
    $this->drupalGet(Url::fromRoute('entity.braintree_cashier_subscription.collection'));
    $this->assertSession()->selectExists('Subscription Type');
    $headers = [
      'Subscribed user',
      'Name',
      'Subscription status',
      'Created',
      'Cancel at period end',
      'Subscription type',
      'Operations links',
    ];
    foreach ($headers as $header) {
      $this->assertSession()->pageTextContains($header);
    }
  }

  /**
   * Tests that the View for administering Billing Plans exists.
   *
   * Checks that the View overrides the entity collection route.
   */
  public function testBillingPlanCollectionExists() {
    $this->drupalGet(Url::fromRoute('entity.braintree_cashier_billing_plan.collection'));
    $this->assertSession()->elementContains('css', 'caption:first-of-type', 'Sandbox');
    // Test table headers exist.
    $headers = [
      'Name',
      'Braintree Plan ID',
      'Is available for purchase',
      'Edit',
    ];
    foreach ($headers as $header) {
      $this->assertSession()->pageTextContains($header);
    }
  }

  /**
   * Tests that the View for administering Discounts exists.
   *
   * Checks that the View overrides the entity collection route.
   */
  public function testDiscountCollectionExists() {
    $this->drupalGet(Url::fromRoute('entity.braintree_cashier_discount.collection'));
    $headers = [
      'The discount ID',
      'The billing plans for which this discount is valid',
      'Operations links',
    ];
    foreach ($headers as $header) {
      $this->assertSession()->pageTextContains($header);
    }
  }

}
