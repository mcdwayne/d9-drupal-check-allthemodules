<?php

namespace Drupal\braintree_cashier\Plugin\QueueWorker;

use Drupal\braintree_cashier\Entity\BraintreeCashierSubscription;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\message\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Populates new date fields on existing Braintree managed subscriptions.
 *
 * @QueueWorker(
 *   id = "populate_subscription_date_fields",
 *   title = @Translation("Populate date fields on existing subscriptions"),
 *   cron = {"time" = 60}
 * )
 */
class PopulateNewDateFields extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The subscription service.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;

  /**
   * Message entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $messageStorage;

  /**
   * PopulateNewDateFields constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SubscriptionService $subscriptionService, EntityStorageInterface $messageStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->subscriptionService = $subscriptionService;
    $this->messageStorage = $messageStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('braintree_cashier.subscription_service'),
      $container->get('entity_type.manager')->getStorage('message')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // $data is a subscription entity id.
    $subscription_entity = BraintreeCashierSubscription::load($data);
    /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan */
    $billing_plan = $subscription_entity->getBillingPlan();
    $braintree_subscription = $this->subscriptionService->asBraintreeSubscription($subscription_entity);

    if ($billing_plan->hasFreeTrial()) {
      // Make sure the subscription actually started with a free trial.
      $started_with_trial = $braintree_subscription->firstBillingDate->getTimestamp() > $subscription_entity->getCreatedTime() + 24 * 3600;
      if ($started_with_trial) {
        // Populate free trial date fields.
        // Trial is presumed to start when subscription was created.
        $subscription_entity->setTrialStartDate($subscription_entity->getCreatedTime());

        if (!empty($braintree_subscription->billingPeriodEndDate)) {
          // Trial is over. Set trial_end _date to the first billing date.
          $subscription_entity->setTrialEndDate($braintree_subscription->firstBillingDate->getTimestamp());
        }
      }
    }

    if ($subscription_entity->getStatus() == BraintreeCashierSubscriptionInterface::CANCELED) {
      // Set subscription end date.
      if (empty($braintree_subscription->billingPeriodEndDate)) {
        // Subscription was set to cancel before it was charged.
        $end_date = $braintree_subscription->firstBillingDate->getTimestamp();
      }
      else {
        // This is an approximation, since retry logic could have made the
        // subscription active for longer than this.
        $end_date = $braintree_subscription->billingPeriodEndDate->getTimestamp();
      }
      $subscription_entity->setEndedAtDate($end_date);
    }

    $cancel_message_ids = $this->messageStorage->getQuery()
      ->condition('template', 'subscription_canceled_by_user')
      ->condition('field_subscription.target_id', $subscription_entity->id())
      ->execute();

    if (!empty($cancel_message_ids)) {
      $cancel_message_id = array_shift($cancel_message_ids);
      $cancel_message = Message::load($cancel_message_id);
      $subscription_entity->setCanceledAtDate($cancel_message->getCreatedTime());
    }

    $subscription_entity->save();

  }

}
