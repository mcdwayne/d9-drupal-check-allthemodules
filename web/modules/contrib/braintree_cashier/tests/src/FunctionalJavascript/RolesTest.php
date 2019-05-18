<?php

namespace Drupal\Tests\braintree_cashier\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test that roles are granted and revoked by signup and cancel.
 *
 * @package Drupal\Tests\braintree_cashier\FunctionalJavascript
 *
 * @group braintree_cashier
 */
class RolesTest extends WebDriverTestBase {

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
   * The account created for the test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * The billing plan entity.
   *
   * @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   */
  protected $billingPlan;

  /**
   * The free trial billing plan entity.
   *
   * @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   */
  protected $freeTrialPlanEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupBraintreeApi();
    $this->billingPlan = $this->createMonthlyBillingPlan();
    $this->createRole([], 'premium', 'Premium');

    $this->billingPlan->set('roles_to_assign', ['premium']);
    $this->billingPlan->set('roles_to_revoke', ['premium']);
    $this->billingPlan->save();

    $this->freeTrialPlanEntity = $this->createMonthlyFreeTrialBillingPlan();

    $this->freeTrialPlanEntity->set('roles_to_assign', ['premium']);
    $this->freeTrialPlanEntity->set('roles_to_revoke', ['premium']);
    $this->freeTrialPlanEntity->save();

    $this->account = $this->drupalCreateUser();
    $this->drupalLogin($this->account);
    $this->drupalGet(Url::fromRoute('braintree_cashier.signup_form'));
  }

  /**
   * Tests that a subscribing user is granted the premium role.
   */
  public function testUserGrantedRole() {
    $this->assertFalse($this->account->hasRole('premium'), 'The user does not have the premium role before checkout.');
    $this->getSession()->getPage()->selectFieldOption('Choose a plan', $this->billingPlan->id());
    $this->fillInCardForm($this, [
      'card_number' => '4242424242424242',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);

    $this->getSession()->getPage()->find('css', '#submit-button')->click();
    $this->assertSession()->waitForElementVisible('css', '.messages--status', 20000);
    $this->assertSession()->pageTextContains('You have been signed up for the CI Monthly plan. Thank you, and enjoy your subscription!');
    // Reload account to pick up new role.
    $this->account = \Drupal::service('entity_type.manager')->getStorage('user')->load($this->account->id());
    $this->assertTrue($this->account->hasRole('premium'), 'The user successfully received the premium role after checkout.');
  }

  /**
   * Tests that a user keeps their premium role after canceling a subscription.
   */
  public function testUserKeepsRolesOnCancel() {
    $this->testUserGrantedRole();
    $this->drupalGet(Url::fromRoute('braintree_cashier.cancel', [
      'user' => $this->account->id(),
    ]));
    $this->getSession()->getPage()->findButton('Cancel my subscription')->click();
    $this->getSession()->getPage()->findButton('Yes, I wish to cancel')->click();

    $this->account = \Drupal::service('entity_type.manager')->getStorage('user')->load($this->account->id());
    $this->assertTrue($this->account->hasRole('premium'), 'The user still has the premium role.');
    $this->assertSession()->pageTextContains('Billing for your subscription has been canceled');
    $this->assertSession()->elementTextContains('css', '.current-subscription-label__suffix', 'Billing has been canceled for this subscription. Access expires on');
  }

  /**
   * Checks that a user's premium role is retained on cron run.
   *
   * The user's subscription period end date is in the past and it's set to
   * cancel at period end, but the subscription type is managed by Braintree.
   * The subscription type is not "Free" in other words. This scenario occurs
   * when Braintree is attempting to re-try failed payment attempts.
   */
  public function testRoleRetainedOnCronWhenBraintreeManaged() {
    $this->testUserKeepsRolesOnCancel();
    /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
    $subscriptions = \Drupal::service('braintree_cashier.billable_user')->getSubscriptions($this->account);
    $subscription = array_shift($subscriptions);
    $subscription->setPeriodEndDate(time() - 100000);
    $subscription->save();
    \Drupal::service('cron')->run();
    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');
    $user_storage->resetCache([$this->account->id()]);
    $this->account = $user_storage->load($this->account->id());
    $this->assertTrue($this->account->hasRole('premium'), 'The user still has the premium role after cron since this subscription type is managed by Braintree.');
  }

  /**
   * Tests that a user's premium role is revoked on cron run.
   *
   * Their subscription period end date is in the past and it's set to cancel at
   * period end, and it is a Free subscription type.
   */
  public function testRoleRevokedOnCron() {
    $this->assertFalse($this->account->hasRole('premium'), 'The user does not have the premium role before checkout.');

    // Sign up for the free trial.
    $this->getSession()->getPage()->selectFieldOption('Choose a plan', $this->freeTrialPlanEntity->id());
    $this->fillInCardForm($this, [
      'card_number' => '4242424242424242',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);

    $this->getSession()->getPage()->find('css', '#submit-button')->click();
    $this->assertSession()->waitForElementVisible('css', '.messages--status', 20000);
    // Reload account to pick up new role.
    $this->account = \Drupal::service('entity_type.manager')->getStorage('user')->load($this->account->id());
    $this->assertTrue($this->account->hasRole('premium'), 'The user successfully received the premium role after checkout.');

    // Cancel the free trial.
    $this->drupalGet(Url::fromRoute('braintree_cashier.cancel', [
      'user' => $this->account->id(),
    ]));
    $this->getSession()->getPage()->findButton('Cancel my subscription')->click();
    $this->getSession()->getPage()->findButton('Yes, I wish to cancel')->click();

    $this->account = \Drupal::service('entity_type.manager')->getStorage('user')->load($this->account->id());
    $this->assertTrue($this->account->hasRole('premium'), 'The user still has the premium role.');
    $this->assertSession()->elementTextContains('css', '.current-subscription-label__suffix', 'Billing has been canceled for this subscription. Access expires on');

    // Set the period end date in the past in order for the subscription to get
    // picked up during the cron run.
    /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
    $subscriptions = \Drupal::service('braintree_cashier.billable_user')->getSubscriptions($this->account);
    $subscription = array_shift($subscriptions);
    $subscription->setPeriodEndDate(time() - 100000);
    $subscription->save();

    \Drupal::service('cron')->run();
    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');
    $user_storage->resetCache([$this->account->id()]);
    $this->account = $user_storage->load($this->account->id());
    $this->assertFalse($this->account->hasRole('premium'), 'The user does not have the premium role after cron.');
  }

}
