<?php

namespace Drupal\Tests\braintree_cashier\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests subscription plan updating.
 */
class UpdatePlanTest extends WebDriverTestBase {

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
  protected $profile = 'standard';

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
    $this->createMonthlyBillingPlan();

    $this->account = $this->drupalCreateUser();
    $this->drupalLogin($this->account);
    $this->drupalGet(Url::fromRoute('braintree_cashier.signup_form'));

    // Sign up for a plan.
    $this->fillInCardForm($this, [
      'card_number' => '4242424242424242',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);

    $this->getSession()->getPage()->find('css', '#submit-button')->click();
    $this->assertSession()->waitForElementVisible('css', '.messages--status', 30000);
    $this->assertSession()->pageTextContains('You have been signed up for the CI Monthly plan. Thank you, and enjoy your subscription!');

  }

  /**
   * Test that updating to a plan of an already active subscription fails.
   *
   * This subscription update should fail if the currently active subscription
   * is not on a grace period.
   */
  public function testUpdateSamePlanFails() {
    $this->drupalGet(Url::fromRoute('braintree_cashier.my_subscription', [
      'user' => $this->account->id(),
    ]));
    $this->getSession()->getPage()->pressButton('Update plan');
    $this->getSession()->getPage()->pressButton('Confirm');
    $this->assertSession()->waitForElementVisible('css', '.messages--error', 10000);
    $this->assertSession()->elementTextContains('css', '.messages--error', 'You already have an active subscription with the CI Monthly for $12 / month plan. No changes have been made.');
  }

  /**
   * Test that a subscription on a grace period may be resumed.
   */
  public function testResumeSubscription() {
    // Cancel the subscription to make it on a grace period.
    $this->drupalGet(Url::fromRoute('braintree_cashier.cancel', [
      'user' => $this->account->id(),
    ]));
    $this->getSession()->getPage()->findButton('Cancel my subscription')->click();
    $this->getSession()->getPage()->findButton('Yes, I wish to cancel')->click();

    // Resume the subscription with the same billing plan.
    $this->drupalGet(Url::fromRoute('braintree_cashier.my_subscription', [
      'user' => $this->account->id(),
    ]));
    $this->getSession()->getPage()->pressButton('Update plan');
    $this->getSession()->getPage()->pressButton('Confirm');

    $this->assertSession()->waitForElementVisible('css', '.messages--status', 30000);
    $this->assertSession()->elementTextContains('css', '.messages--status', 'Your subscription has been updated!');
    $this->assertSession()->elementTextContains('css', '.current-subscription-label', 'CI Monthly');
  }

}
