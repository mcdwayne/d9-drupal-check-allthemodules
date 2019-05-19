<?php

namespace Drupal\stripe_webhooks_example\EventSubscriber;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\stripe_webhooks\Event\SubscriptionEvent;
use Drupal\stripe_webhooks\Event\SubscriptionEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to Stripe Webhook events.
 */
class StripeWebhookSubscriptionSubscriber implements EventSubscriberInterface {

  /**
   * Stripe Webhooks logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  public function __construct(LoggerChannelFactory $logger_channel, MailManagerInterface $mail_manager) {
    $this->logger = $logger_channel->get('stripe_webhooks_example');
    $this->mailManager = $mail_manager;
  }

  /**
   * Notify user about the new subscription.
   *
   * @param \Drupal\stripe_webhooks\Event\SubscriptionEvent $event
   */
  public function notifyNewSubscription(SubscriptionEvent $event) {
    try {
      // Get the Stripe event.
      $subscription = $event->getSubscription();

      // Get the customer data.
      $customer = $event->getCustomer();

      // Send the email.
      $to = $customer->email;
      $subject = 'Your subscription has been received';
      $message[] = 'Hi!';
      $message[] = "We've received your subscription:";
      $message[] = '';
      $message[] = '-------------------------------------------------';
      $message[] = 'SUBSCRIPTION RECEIPT';
      $message[] = '';
      $message[] = 'Email: ' . $customer->email;
      $message[] = 'Quantity: ' . $subscription->__get('data')['object']['quantity'];
      $message[] = 'Amount: ' . sprintf('%0.2f', $subscription->__get('data')['object']['plan']['amount'] / 100.0);
      $message[] = 'Currency: ' . $subscription->__get('data')['object']['plan']['currency'];
      $message[] = 'Interval: ' . $subscription->__get('data')['object']['plan']['interval_count'] . ' ' . $subscription->__get('data')['object']['plan']['interval'];
      $message[] = '';
      $message[] = '-------------------------------------------------';
      $message[] = '';
      $message[] = 'Thanks for your subscription!';

      $langcode = LanguageInterface::LANGCODE_SITE_DEFAULT;

      $params = [
        'subject' => $subject,
        'message' => implode("\r\n", $message),
      ];

      // Set a unique key for this mail.
      $key = 'stripe_webhooks_example_subscription_event';

      $message = $this->mailManager->mail('stripe_webhooks_example', $key, $to, $langcode, $params);
      if ($message['result']) {
        $this->logger->notice('Successfully sent email to %recipient.', ['%recipient' => $to]);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('stripe_webhooks_example', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SubscriptionEvents::CUSTOMER_SUBSCRIPTION_CREATED][] = ['notifyNewSubscription'];

    return $events;
  }

}
