<?php

namespace Drupal\stripe_licensing\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\licensing\LicensingService;
use Drupal\stripe_api\Event\StripeApiWebhookEvent;
use Drupal\stripe_api\StripeApiService;
use Stripe\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class WebHookSubscriber.
 *
 * @package Drupal\stripe_registration
 */
class WebHookSubscriber implements EventSubscriberInterface {

  /**
   * @var StripeApiService*/
  protected $stripeApi;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface*/
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface*/
  protected $logger;

  /**
   * @var \Drupal\licensing\LicensingService*/
  protected $licensing;

  /**
   * WebHookSubscriber constructor.
   *
   * @param \Drupal\stripe_api\StripeApiService $stripe_api
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @internal param \Drupal\stripe_registration\StripeRegistrationService $stripe_registration_stripe_api
   */
  public function __construct(StripeApiService $stripe_api, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger_channel, LicensingService $licensing) {
    $this->stripeApi = $stripe_api;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_channel;
    $this->licensing = $licensing;
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events['stripe_api.webhook'][] = ['onIncomingWebhook'];
    return $events;
  }

  /**
   * Process an incoming webhook.
   *
   * @param \Drupal\stripe_api\Event\StripeApiWebhookEvent $event
   *   Logs an incoming webhook of the setting is on.
   */
  public function onIncomingWebhook(StripeApiWebhookEvent $event) {
    $type = $event->type;
    $data = $event->data;
    /** @var Event $stripe_event */
    $stripe_event = $event->event;

    switch ($type) {
      case 'charge.succeeded':
        $this->logger->info("Reacting to charge.succeeded webhook from Stripe.");

        /** @var \Stripe\Charge $charge */
        $charge = $data->object;

        if (property_exists($charge, 'module') && $charge->module == 'stripe_checkout') {

          if (!property_exists($charge, 'uid')) {
            $this->logger->error("Charge object is missing uid property, cannot create update license.");
            return;
          }
          if (!property_exists($charge, 'entity_id')) {
            $this->logger->error("Charge object is missing entity_id property, cannot create update license.");
            return;
          }

          $uid = $charge->metadata->uid;
          $entity_id = $charge->metadata->entity_id;
          // @todo Verify that this entity type should be licensed.
          $this->licensing->createOrUpdateLicense($uid, $entity_id, LICENSE_ACTIVE);
        }

        break;
    }

  }

}
