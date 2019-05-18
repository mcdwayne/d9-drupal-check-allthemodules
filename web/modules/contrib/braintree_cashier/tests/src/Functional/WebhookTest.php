<?php

namespace Drupal\Tests\braintree_cashier\Functional;

use Drupal\braintree_cashier\Entity\BraintreeCashierSubscription;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\Core\Url;
use Drupal\Tests\braintree_cashier\FunctionalJavascript\BraintreeCashierTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests receiving webhooks from Braintree.
 */
class WebhookTest extends BrowserTestBase {

  use BraintreeCashierTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['braintree_cashier', 'braintree_api_test'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The free trial user account created by this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $trialAccount;

  /**
   * The paid user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $paidAccount;

  /**
   * The free trial subscription entity id.
   *
   * @var string
   */
  protected $trialSubscriptionEntityId;

  /**
   * The paid subscription entity id.
   *
   * @var string
   */
  protected $paidSubscriptionEntityId;

  /**
   * The Braintree API service.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupBraintreeApi();
    $this->braintreeApi = \Drupal::service('braintree_api.braintree_api');
    $billing_plan = $this->createMonthlyBillingPlan();
    $this->createRole([], 'premium', 'Premium');

    $billing_plan->set('roles_to_assign', ['premium']);
    $billing_plan->set('roles_to_revoke', ['premium']);
    $billing_plan->save();

    $this->trialAccount = $this->drupalCreateUser();
    $this->paidAccount = $this->drupalCreateUser();

    $trial_subscription_entity = BraintreeCashierSubscription::create([
      'subscription_type' => $billing_plan->getSubscriptionType(),
      'subscribed_user' => $this->trialAccount->id(),
      'status' => BraintreeCashierSubscriptionInterface::ACTIVE,
      'name' => $billing_plan->getName(),
      'billing_plan' => $billing_plan->id(),
      'roles_to_assign' => $billing_plan->getRolesToAssign(),
      'roles_to_revoke' => $billing_plan->getRolesToRevoke(),
      'braintree_subscription_id' => '123',
      'is_trialing' => TRUE,
    ]);
    $paid_subscription_entity = BraintreeCashierSubscription::create([
      'subscription_type' => $billing_plan->getSubscriptionType(),
      'subscribed_user' => $this->paidAccount->id(),
      'status' => BraintreeCashierSubscriptionInterface::ACTIVE,
      'name' => $billing_plan->getName(),
      'billing_plan' => $billing_plan->id(),
      'roles_to_assign' => $billing_plan->getRolesToAssign(),
      'roles_to_revoke' => $billing_plan->getRolesToRevoke(),
      'braintree_subscription_id' => '321',
      'is_trialing' => FALSE,
    ]);
    $trial_subscription_entity->save();
    $paid_subscription_entity->save();
    $this->trialSubscriptionEntityId = $trial_subscription_entity->id();
    $this->paidSubscriptionEntityId = $paid_subscription_entity->id();
  }

  /**
   * Test subscription canceled webhook.
   *
   * Tests that a webhook received from Braintree that notifies that a
   * subscription has been canceled results in the subscription entity
   * still being active since it's on a free trial.
   */
  public function testSubscriptionCanceledWebhook() {
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Confirm that the subscribed user has the premium role.
    /** @var \Drupal\user\Entity\User $trialAccount */
    $trialAccount = $user_storage->load($this->trialAccount->id());
    $this->assertTrue($trialAccount->hasRole('premium'), 'User has the premium role before webhook received.');

    // Create a sample webhook and submit it. The form will POST to the webhook
    // url /braintree/webhooks, simulating the same POST request from Braintree.
    $sample_notification = $this->braintreeApi->getGateway()->webhookTesting()->sampleNotification(\Braintree_WebhookNotification::SUBSCRIPTION_CANCELED, '123');
    $this->drupalPostForm(Url::fromRoute('braintree_api_test.webhook_notification_test_form'), [
      'bt_signature' => $sample_notification['bt_signature'],
      'bt_payload' => $sample_notification['bt_payload'],
    ], 'Submit');
    $this->assertSession()->pageTextContains('Thanks!');

    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('process_subscription_webhook');
    $queue = \Drupal::queue('process_subscription_webhook');
    $item = $queue->claimItem();
    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);

    // Reset the cache and check that the premium role was removed.
    $user_storage->resetCache([$this->trialAccount->id()]);
    $trialAccount = $user_storage->load($this->trialAccount->id());
    $this->assertTrue($trialAccount->hasRole('premium'), 'The user still has the premium role after the "subscription_canceled" webhook was received after canceling a free trial.');
    $this->drupalLogin($this->trialAccount);
    $this->drupalGet('user/' . $this->trialAccount->id() . '/subscription');
    $this->assertSession()->elementTextContains('css', '.current-subscription-label__suffix', 'Billing has been canceled for this subscription. Access expires on');
  }

  /**
   * Tests the subscription_expired webhook from Braintree.
   *
   * Tests that a 'subscription_expired' webhook notification from Braintree
   * will result in the corresponding subscription entity having it's status
   * set to 'canceled' and the subscribed user will have the premium role
   * revoked.
   */
  public function testSubscriptionExpiredWebhook() {
    $subscription_storage = $this->container->get('entity_type.manager')->getStorage('braintree_cashier_subscription');
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Confirm that the subscribed user has the premium role.
    /** @var \Drupal\user\Entity\User $paidAccount */
    $paidAccount = $user_storage->load($this->paidAccount->id());
    $this->assertTrue($paidAccount->hasRole('premium'), 'User has the premium role before webhook received.');

    // Confirm that the subscription is active.
    /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription_entity */
    $subscription_entity = $subscription_storage->load($this->paidSubscriptionEntityId);
    $this->assertTrue($subscription_entity->getStatus() == BraintreeCashierSubscriptionInterface::ACTIVE, 'The subscription is active before expired webhook');

    // Create a sample webhook and submit it. The form will POST to the webhook
    // url /braintree/webhooks, simulating the same POST request from Braintree.
    $sample_notification = $this->braintreeApi->getGateway()->webhookTesting()->sampleNotification(\Braintree_WebhookNotification::SUBSCRIPTION_EXPIRED, '321');
    $this->drupalPostForm(Url::fromRoute('braintree_api_test.webhook_notification_test_form'), [
      'bt_signature' => $sample_notification['bt_signature'],
      'bt_payload' => $sample_notification['bt_payload'],
    ], 'Submit');
    $this->assertSession()->pageTextContains('Thanks!');

    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('process_subscription_webhook');
    $queue = \Drupal::queue('process_subscription_webhook');
    $item = $queue->claimItem();
    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);

    $this->drupalLogin($this->paidAccount);
    $this->drupalGet(Url::fromRoute('braintree_cashier.my_subscription', [
      'user' => $this->paidAccount->id(),
    ]));
    $this->assertSession()->elementTextContains('css', '.current-subscription-label', 'None');

    // Reset the cache and check that the subscription is canceled.
    $subscription_storage->resetCache([$this->paidSubscriptionEntityId]);
    $subscription_entity = $subscription_storage->load($this->paidSubscriptionEntityId);
    $this->assertTrue($subscription_entity->getStatus() == BraintreeCashierSubscriptionInterface::CANCELED, 'The subscription is canceled after the expired webhook');

    // Reset the cache and check that the premium role was removed.
    $user_storage->resetCache([$this->paidAccount->id()]);
    $paidAccount = $user_storage->load($this->paidAccount->id());
    $this->assertFalse($paidAccount->hasRole('premium'), 'The user does not have the premium role after the "subscription_expired" webhook was received');
  }

}
