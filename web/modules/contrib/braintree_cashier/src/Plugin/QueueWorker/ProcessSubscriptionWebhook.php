<?php

namespace Drupal\braintree_cashier\Plugin\QueueWorker;

use Drupal\braintree_cashier\BraintreeCashierService;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\message\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process subscription webhooks received from Braintree.
 *
 * @QueueWorker(
 *   id = "process_subscription_webhook",
 *   title = @Translation("Process a webhook received from Braintree"),
 *   cron = {"time" = 60}
 * )
 */
class ProcessSubscriptionWebhook extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The Braintree Cashier logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Braintree Cashier service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * The subscription service.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;

  /**
   * ProcessSubscriptionWebhook constructor.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, LoggerChannelInterface $logger, BraintreeCashierService $bcService, SubscriptionService $subscriptionService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->bcService = $bcService;
    $this->subscriptionService = $subscriptionService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.braintree_cashier'),
      $container->get('braintree_cashier.braintree_cashier_service'),
      $container->get('braintree_cashier.subscription_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $kind = $data['kind'];
    $braintree_subscription = $data['braintree_subscription'];
    try {
      $subscription_entity = $this->subscriptionService->findSubscriptionEntity($braintree_subscription->id);
    }
    catch (\Exception $e) {
      $this->bcService->sendAdminErrorEmail($e->getMessage());
      watchdog_exception('subscription_entity_not_found', $e);
      // By throwing another exception this item will be processed again later.
      throw new \Exception('The subscription entity for Braintree ID ' . $braintree_subscription->id . ' could not be found.');
    }

    if ($kind == \Braintree_WebhookNotification::SUBSCRIPTION_CANCELED && $subscription_entity->getStatus() == BraintreeCashierSubscriptionInterface::ACTIVE) {
      // The nextBillingDate will be empty only for webhooks simulated by
      // \Braintree\WebhookTestingGateway::_subscriptionSampleXml.
      $is_test_webhook = empty($braintree_subscription->nextBillingDate);
      if (empty($braintree_subscription->billingPeriodEndDate)) {
        // Set a period end date for canceled free trials.
        // billingPeriodEndDate is empty only for free trial subscriptions.
        if ($is_test_webhook) {
          $braintree_subscription->nextBillingDate = new \DateTime("2019-01-01");
        }
        $subscription_entity->setPeriodEndDate($braintree_subscription->nextBillingDate->getTimestamp());
        $subscription_entity->setCancelAtPeriodEnd(TRUE);
      }
      else {
        $subscription_entity->setStatus(BraintreeCashierSubscriptionInterface::CANCELED);
      }
      $subscription_entity->save();
      $message = Message::create([
        'template' => 'subscription_canceled_by_webhook',
        'uid' => $subscription_entity->getSubscribedUser()->id(),
        'field_subscription' => $subscription_entity->id(),
      ]);
      $message->save();
    }

    if ($kind == \Braintree_WebhookNotification::SUBSCRIPTION_EXPIRED && $subscription_entity->getStatus() == BraintreeCashierSubscriptionInterface::ACTIVE) {
      $subscription_entity->setStatus(BraintreeCashierSubscriptionInterface::CANCELED);
      $subscription_entity->save();
      $message = Message::create([
        'template' => 'subscription_expired_by_webhook',
        'uid' => $subscription_entity->getSubscribedUser()->id(),
        'field_subscription' => $subscription_entity->id(),
      ]);
      $message->save();
    }

    if ($kind == \Braintree_WebhookNotification::SUBSCRIPTION_TRIAL_ENDED && $subscription_entity->isTrialing()) {
      $subscription_entity->setIsTrialing(FALSE);
      $subscription_entity->setTrialEndDate(time());
      $subscription_entity->save();
      $message = Message::create([
        'template' => 'free_trial_ended',
        'uid' => $subscription_entity->getSubscribedUser()->id(),
        'field_subscription' => $subscription_entity->id(),
      ]);
      $message->save();
    }
  }

}
