<?php

namespace Drupal\commerce_mautic\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\mautic_api\MauticApiServiceInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends a receipt email when an order is placed.
 */
class OrderContactSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\mautic_api\MauticApiServiceInterface
   */
  protected $mauticApiService;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The immutable entity clone settings configuration entity.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * OrderReceiptSubscriber constructor.
   *
   * @param \Drupal\mautic_api\MauticApiServiceInterface $mautic_api_service
   */
  public function __construct(MauticApiServiceInterface $mautic_api_service, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->mauticApiService = $mautic_api_service;
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('commerce_mautic.settings');
  }

  /**
   * Sends an order receipt email.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function createMauticContact(WorkflowTransitionEvent $event) {
    if (!$this->config->get('order_finished_add_contact')) {
      return;
    }
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    // We collect the basic data from the billing profile.
    $profile_data = [];
    $profile = $order->getBillingProfile();
    if ($profile->bundle() == 'customer' && !$profile->address->isEmpty()) {
      $profile_data['firstname'] = $profile->address->given_name;
      $profile_data['lastname'] = $profile->address->family_name;
    }
    // We allow other modules to add additional information.
    $this->moduleHandler->alter('commerce_mautic_order_data', $profile_data, $order);
    // We create a contact in mautic.
    $contact = $this->mauticApiService->createContact($order->getEmail(), $profile_data);

    if ($this->config->get('order_finished_send_mail')) {
      $email = $this->mauticApiService->sendEmailToContact($this->config->get('order_finished_email_id'), $contact['id']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.place.post_transition' => ['createMauticContact', 100]];
    return $events;
  }

}
