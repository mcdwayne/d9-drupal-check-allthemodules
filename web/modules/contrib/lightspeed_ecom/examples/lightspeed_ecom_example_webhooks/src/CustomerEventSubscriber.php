<?php

namespace Drupal\lightspeed_ecom_example_webhooks;

use Drupal\lightspeed_ecom\Service\WebhookEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventSubscriber.
 *
 * @package Drupal\lightspeed_ecom_example_webhooks
 */
class CustomerEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $log;

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $log
   */
  public function __construct(LoggerInterface $log) {
    $this->log = $log;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[WebhookEvent::CUSTOMERS_CREATED][] = array('onCreate');
    $events[WebhookEvent::CUSTOMERS_UPDATED][] = array('onUpdate');
    $events[WebhookEvent::CUSTOMERS_DELETED][] = array('onDelete');
    return $events;
  }

  /**
   * Handler for customer created event.
   *
   * @param \Drupal\lightspeed_ecom\Service\WebhookEvent $event
   *   The customer created event.
   */
  public function onCreate(WebhookEvent $event) {
    $this->log->info('Customer {id} created: {customer}', [
      'id' => $event->getObjectId(),
      'customer' => json_encode($event->getPayload(), JSON_PRETTY_PRINT),
    ]);
  }

  /**
   * Handler for customer updated event.
   *
   * @param \Drupal\lightspeed_ecom\Service\WebhookEvent $event
   *   The customer updated event.
   */
  public function onUpdate(WebhookEvent $event) {
    $this->log->info('Customer {id} updated: {customer}', [
      'id' => $event->getObjectId(),
      'customer' => json_encode($event->getPayload(), JSON_PRETTY_PRINT),
    ]);
  }

  /**
   * Handler for customer deleted event.
   *
   * @param \Drupal\lightspeed_ecom\Service\WebhookEvent $event
   *   The customer deleted event.
   */
  public function onDelete(WebhookEvent $event) {
    $this->log->info('Customer {id} deleted: {customer}', [
      'id' => $event->getObjectId(),
      'customer' => json_encode($event->getPayload(), JSON_PRETTY_PRINT),
    ]);
  }

}
