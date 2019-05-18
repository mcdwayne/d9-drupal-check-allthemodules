<?php

namespace Drupal\braintree_cashier\Plugin\QueueWorker;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\BraintreeCashierService;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\DecimalMoneyParser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves free trials that will be expiring soon.
 *
 * @QueueWorker(
 *   id = "retrieve_expiring_free_trials",
 *   title = @Translation("Retrieve expiring free trials"),
 *   cron = {"time" = 60}
 * )
 */
class RetrieveExpiringFreeTrials extends QueueWorkerBase implements ContainerFactoryPluginInterface {


  /**
   * The Braintree API service.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * Braintree Cashier configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $bcConfig;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The subscription service.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;

  /**
   * The decimal money parser.
   *
   * @var \Money\Parser\DecimalMoneyParser
   */
  protected $moneyParser;

  /**
   * The international money formatter.
   *
   * @var \Money\Formatter\IntlMoneyFormatter
   */
  protected $moneyFormatter;

  /**
   * The Braintree Cashier service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * The subscriptions to notify store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $freeTrialNotificationsStore;

  /**
   * The Braintree Cashier logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BraintreeApiService $braintreeApi, ConfigFactoryInterface $configFactory, QueueFactory $queueFactory, SubscriptionService $subscriptionService, BraintreeCashierService $bcService, KeyValueFactoryInterface $keyValueFactory, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->braintreeApi = $braintreeApi;
    $this->bcConfig = $configFactory->get('braintree_cashier.settings');
    $this->queueFactory = $queueFactory;
    $this->subscriptionService = $subscriptionService;
    $this->bcService = $bcService;
    $this->freeTrialNotificationsStore = $keyValueFactory->get('queued_free_trial_notifications');
    $this->logger = $logger;

    // Setup Money.
    $currencies = new ISOCurrencies();
    $this->moneyParser = new DecimalMoneyParser($currencies);
    $numberFormatter = new \NumberFormatter($this->bcService->getLocale(), \NumberFormatter::CURRENCY);
    $this->moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('braintree_api.braintree_api'),
      $container->get('config.factory'),
      $container->get('queue'),
      $container->get('braintree_cashier.subscription_service'),
      $container->get('braintree_cashier.braintree_cashier_service'),
      $container->get('keyvalue.expirable'),
      $container->get('logger.channel.braintree_cashier')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $lookaheadPeriod = new \DateTime();
    $lookaheadString = '+' . $this->bcConfig->get('free_trial_notification_period') . ' days';
    $lookaheadPeriod->modify($lookaheadString);

    // Find upcoming active subscription on a free trial.
    $braintree_subscriptions = $this->braintreeApi->getGateway()->subscription()->search([
      \Braintree_SubscriptionSearch::status()->in(
        [\Braintree_Subscription::ACTIVE]
      ),
      \Braintree_SubscriptionSearch::inTrialPeriod()->is(TRUE),
      \Braintree_SubscriptionSearch::nextBillingDate()
        ->lessThanOrEqualTo($lookaheadPeriod),
    ]);

    $items = [];

    // Using the key-value store to record queued free trial notifications was
    // inspired by \Drupal\update\UpdateProcessor::createFetchTask.
    $queuedFreeTrialNotifications = $this->freeTrialNotificationsStore->getAll();
    foreach ($braintree_subscriptions as $braintree_subscription) {
      $subscription_entity = $this->subscriptionService->findSubscriptionEntity($braintree_subscription->id);
      if (empty($queuedFreeTrialNotifications[$subscription_entity->id()])) {
        $currency_code = $this->bcConfig->get('currency_code');
        $amount = $this->moneyParser->parse($braintree_subscription->nextBillingPeriodAmount, $currency_code);
        $items[] = [
          'subscription_entity_id' => $subscription_entity->id(),
          'amount' => $this->moneyFormatter->format($amount),
          'currency_code' => $currency_code,
          'next_billing_date' => $braintree_subscription->nextBillingDate->getTimestamp(),
        ];
        // Assumes free trial expiry notification will be sent within 30 days.
        $duration_in_key_value_store = 3600 * 24 * 30;
        // Expire the entry in the key-value store to avoid clogging the DB.
        $this->freeTrialNotificationsStore->setWithExpireIfNotExists($subscription_entity->id(), 'uid: ' . $subscription_entity->getSubscribedUserId(), $duration_in_key_value_store);
        if ($this->bcConfig->get('debug')) {
          $this->logger->info('Queued subscription with entity id %id for free trial ending notification.', ['%id' => $subscription_entity->id()]);
        }
      }
    }

    // Sort by next billing date.
    $next_billing_date_column = [];
    foreach ($items as $key => $item) {
      $next_billing_date_column[$key] = $item['next_billing_date'];
    }
    array_multisort($next_billing_date_column, SORT_DESC, $items);

    $notificationQueue = $this->queueFactory->get('free_trial_expiring_notifier', TRUE);
    foreach ($items as $item) {
      $notificationQueue->createItem($item);
    }
    if ($this->bcConfig->get('debug')) {
      $this->logger->info('Finished checking for free trials that are ending within @days days', ['@days' => $this->bcConfig->get('free_trial_notification_period')]);
    }
  }

}
