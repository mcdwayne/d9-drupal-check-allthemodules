<?php

namespace Drupal\Tests\braintree_cashier\FunctionalJavascript;

use Drupal\braintree_cashier\Entity\BraintreeCashierSubscription;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\Entity\User;

/**
 * Test that subscriptions are canceled when a user is canceled.
 */
class CancelUserTest extends WebDriverTestBase {

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
   * The subscriber account created for the test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $subscriberAccount;

  /**
   * The Billing Plan entity.
   *
   * @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   */
  protected $billingPlan;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupBraintreeApi();
    $this->billingPlan = $this->createMonthlyBillingPlan();

    $this->subscriberAccount = $this->drupalCreateUser();
    $this->drupalLogin($this->subscriberAccount);
    $this->drupalGet(Url::fromRoute('braintree_cashier.signup_form'));
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
   * Tests that when a user is blocked, their subscription is canceled.
   */
  public function testSubscriptionCanceledWhenUserBlocked() {

    // Confirm that the subscriber has a subscription.
    $this->drupalGet(Url::fromRoute('braintree_cashier.my_subscription', [
      'user' => $this->subscriberAccount->id(),
    ]));

    $this->assertSession()->elementTextContains('css', '.my-subscription',
      'CI Monthly');
    $this->drupalLogout();
    $this->drupalLogin($this->rootUser);

    // Block the subscriber.
    // See \Drupal\Tests\user\Functional\UserCancelTest::testUserCancelByAdmin.
    $this->config('user.settings')->set('cancel_method', 'user_cancel_block')->save();

    $this->drupalGet('user/' . $this->subscriberAccount->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    // Confirm blocking the subscriber.
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));

    // Confirm no subscription.
    $this->drupalGet(Url::fromRoute('braintree_cashier.my_subscription', [
      'user' => $this->subscriberAccount->id(),
    ]));

    $this->assertSession()->elementTextContains('css', '.my-subscription',
      'None');

    // Visit the invoices tab to confirm no upcoming charges.
    $this->drupalGet(Url::fromRoute('braintree_cashier.invoices', [
      'user' => $this->subscriberAccount->id(),
    ]));
    $this->assertSession()->elementTextContains('css', '.upcoming-invoice',
      'There are no upcoming charges.');
  }

  /**
   * Tests that the subscription entity is deleted upon account deletion.
   */
  public function testSubscriptionDeletedWhenUserDeleted() {
    // Get the entity id of the user's subscription.
    /** @var \Drupal\braintree_cashier\BillableUser $billable_user_service */
    $billable_user_service = \Drupal::service('braintree_cashier.billable_user');
    $subscriptions = $billable_user_service->getSubscriptions($this->subscriberAccount);
    $subscription_entity = array_shift($subscriptions);

    // Confirm the user has this subscription.
    $this->drupalLogin($this->subscriberAccount);
    // Confirm that the subscriber has a subscription.
    $this->drupalGet(Url::fromRoute('braintree_cashier.my_subscription', [
      'user' => $this->subscriberAccount->id(),
    ]));

    $this->assertSession()->elementTextContains('css', '.my-subscription',
      'CI Monthly');
    $this->drupalLogout();

    // Delete the user account.
    $this->drupalLogin($this->rootUser);
    // Subscription entities are not re-assigned to the anonymous user.
    // They are deleted instead.
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();
    $this->drupalGet('user/' . $this->subscriberAccount->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));

    // Confirm deleting the subscriber.
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));

    $this->assertSession()->waitForElementVisible('css', '.messages--status', 20000);

    $this->assertFalse(User::load($this->subscriberAccount->id()), 'User is not found in the database.');
    \Drupal::entityTypeManager()->getStorage('braintree_cashier_subscription')->resetCache();
    $this->assertFalse(BraintreeCashierSubscription::load($subscription_entity->id()), 'Subscription is not found in the database.');
  }

}
