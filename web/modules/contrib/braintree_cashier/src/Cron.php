<?php

namespace Drupal\braintree_cashier;

use Drupal\braintree_cashier\Entity\BraintreeCashierSubscription;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\StateInterface;

/**
 * Default cron implementation.
 */
class Cron implements CronInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The subscription service.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;

  /**
   * The state system.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new Cron object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\braintree_cashier\SubscriptionService $subscription_service
   *   The subscription service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SubscriptionService $subscription_service, StateInterface $state, QueueFactory $queue_factory, ConfigFactoryInterface $config_factory, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->subscriptionService = $subscription_service;
    $this->state = $state;
    $this->queue = $queue_factory;
    $this->config = $config_factory->get('braintree_cashier.settings');
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Cancel free subscriptions entities that are set to cancel at period end,
    // and which have a period end date earlier than now. Subscriptions of other
    // types are canceled by Braintree webhook notifications.
    $subscription_storage = $this->entityTypeManager->getStorage('braintree_cashier_subscription');
    $subscription_ids_will_cancel = $subscription_storage->getQuery()
      ->condition('cancel_at_period_end', TRUE)
      ->exists('period_end_date')
      ->condition('status', BraintreeCashierSubscriptionInterface::ACTIVE)
      ->execute();

    $subscriptions = BraintreeCashierSubscription::loadMultiple($subscription_ids_will_cancel);
    foreach ($subscriptions as $subscription) {
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
      if (!$this->subscriptionService->isBraintreeManaged($subscription) && $subscription->getPeriodEndDate() < time()) {
        $subscription->setStatus(BraintreeCashierSubscriptionInterface::CANCELED);
        $subscription->save();
      }
    }

    // Periodically retrieve expiring free trials for notification.
    $last_check = $this->state->get('braintree_cashier.last_free_trial_expiring_check', 0);
    $free_trial_notification_period = $this->config->get('free_trial_notification_period');
    if ($free_trial_notification_period > 0 && $this->time->getRequestTime() > strtotime('+8 hours', $last_check)) {
      $queue = $this->queue->get('retrieve_expiring_free_trials');
      $queue->createItem($this->time->getRequestTime());
      $this->state->set('braintree_cashier.last_free_trial_expiring_check', $this->time->getRequestTime());
    }
  }

}
