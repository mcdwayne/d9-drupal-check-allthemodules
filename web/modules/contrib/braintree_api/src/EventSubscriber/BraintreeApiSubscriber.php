<?php

namespace Drupal\braintree_api\EventSubscriber;

use Drupal\braintree_api\Event\BraintreeApiEvents;
use Drupal\braintree_api\Event\BraintreeApiWebhookEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BraintreeApiSubscriber is an event subscriber.
 */
class BraintreeApiSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The Braintree API logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * BraintreeApiSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The Braintree API logger channel.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[BraintreeApiEvents::WEBHOOK][] = ['processWebhook'];

    return $events;
  }

  /**
   * Process the "Check URL" webhook from Braintree.
   *
   * @param \Drupal\braintree_api\Event\BraintreeApiWebhookEvent $event
   *   The event to process.
   */
  public function processWebhook(BraintreeApiWebhookEvent $event) {
    $webhook_notification = $event->getWebhookNotification();
    if ($webhook_notification->kind == 'check') {
      $this->logger->info($this->t('A test webhook was received from Braintree.'));
    }
  }

}
