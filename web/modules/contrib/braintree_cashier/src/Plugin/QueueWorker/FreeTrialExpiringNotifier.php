<?php

namespace Drupal\braintree_cashier\Plugin\QueueWorker;

use Drupal\braintree_cashier\Entity\BraintreeCashierSubscription;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Notify users about upcoming free trial expiration.
 *
 * @QueueWorker(
 *   id = "free_trial_expiring_notifier",
 *   title = @Translation("Free trial expiring notifier"),
 *   cron = {"time" = 60}
 * )
 */
class FreeTrialExpiringNotifier extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The message notifier.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * The Braintree Cashier logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * FreeTrialExpiringNotifier constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, MessageNotifier $messageNotifier, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->messageNotifier = $messageNotifier;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('message_notify.sender'),
      $container->get('logger.channel.braintree_cashier')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $subscription_entity = BraintreeCashierSubscription::load($data['subscription_entity_id']);
    if (is_null($subscription_entity) || !$subscription_entity->isTrialing()) {
      return;
    }
    $message = Message::create([
      'template' => 'free_trial_expiring_notification',
      'uid' => $subscription_entity->getSubscribedUserId(),
      'arguments' => [
        '@free_trial_notification_period' => $this->configFactory->get('braintree_cashier.settings')->get('free_trial_notification_period'),
        '@amount' => $data['amount'],
      ],
      'field_subscription' => $subscription_entity->id(),
    ]);
    $message->save();
    $this->messageNotifier->send($message);
    $this->logger->info('Sent free trial ending notification for subscription with entity id %id', ['%id' => $subscription_entity->id()]);
  }

}
