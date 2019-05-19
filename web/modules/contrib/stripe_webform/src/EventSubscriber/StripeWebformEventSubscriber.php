<?php

namespace Drupal\stripe_webform\EventSubscriber;

use Drupal\stripe_webform\Event\StripeWebformWebhookEvent;
use Drupal\stripe\Event\StripeEvents;
use Drupal\stripe\Event\StripeWebhookEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StripeWebformEventSubscriber implements EventSubscriberInterface {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config_factory;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface;
   */
  protected $event_dispatcher;

  /**
   * The iogger interface
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;


  /**
   * Constructs a new instance.
   *
   * @param EventDispatcherInterface $dispatcher
   *   An EventDispatcherInterface instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EventDispatcherInterface $dispatcher, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->event_dispatcher = $dispatcher;
    $this->entity_type_manager = $entity_type_manager;
    $this->config_factory = $config_factory;
    $this->logger = $logger;
  }

  public function handle(StripeWebhookEvent $event) {
    $uuid = $this->config_factory->get('system.site')->get('uuid');
    $stripe_event = $event->getEvent();

    if (!empty($stripe_event['data']['object']['metadata']['webform_submission_id'])) {
      $metadata = $stripe_event['data']['object']['metadata'];
    }
    elseif (!empty($stripe_event['data']['object']['customer'])) {
      $customer = $stripe_event['data']['object']['customer'];
      try {
        $customer = \Stripe\Customer::retrieve($customer);

        if (isset($customer['metadata']['webform_submission_id'])) {
          $metadata = $customer['metadata'];
        }
      } catch (\Stripe\Error\Base $e) {
        $this->logger->error('Stripe API Error: ' . $e->getMessage());
      }
    }

    if (!empty($metadata) && !empty($metadata['uuid']) && $metadata['uuid'] == $uuid) {
      $webform_submission_id = $metadata['webform_submission_id'];

      $webform_submission = $this->entity_type_manager
        ->getStorage('webform_submission')->load($webform_submission_id);
      if ($webform_submission) {
        $webhook_event = new StripeWebformWebhookEvent($stripe_event['type'], $webform_submission, $stripe_event);
        $this->event_dispatcher
          ->dispatch(StripeWebformWebhookEvent::EVENT_NAME, $webhook_event);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[StripeEvents::WEBHOOK][] = array('handle');
    return $events;
  }
}
