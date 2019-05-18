<?php

namespace Drupal\ga_commerce\EventSubscriber;

use Drupal\ga\AnalyticsCommand\RequirePlugin;
use Drupal\ga\AnalyticsEvents;
use Drupal\ga\Event\CollectEvent;
use Drupal\ga_commerce\AnalyticsCommand\EcommerceAddItem;
use Drupal\ga_commerce\AnalyticsCommand\EcommerceAddTransaction;
use Drupal\ga_commerce\AnalyticsCommand\EcommerceSend;
use Drupal\ga_commerce\DelayedCommandRegistryInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sending Googalytics tracking commands for placed Commerce orders.
 */
class CommerceCommandSubscriber implements EventSubscriberInterface {

  /**
   * The delayed command registry.
   *
   * @var \Drupal\ga_commerce\DelayedCommandRegistryInterface
   */
  protected $delayedCommandRegistry;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      AnalyticsEvents::COLLECT => ['trackRegisteredEvents'],
      'commerce_order.place.post_transition' => ['onOrderPlacement', -100],
    ];
    return $events;
  }

  /**
   * Constructs a new CommerceCommandSubscriber object.
   *
   * @param \Drupal\ga_commerce\DelayedCommandRegistryInterface $delayed_command_registry
   *   The delayed command registry service.
   */
  public function __construct(DelayedCommandRegistryInterface $delayed_command_registry) {
    $this->delayedCommandRegistry = $delayed_command_registry;
  }

  /**
   * Registers an Analytics event on placing an order.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function onOrderPlacement(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    $this->delayedCommandRegistry->addCommand(new RequirePlugin('ecommerce'));
    $this->delayedCommandRegistry->addCommand(new EcommerceAddTransaction($order));
    foreach ($order->getItems() as $item) {
      $this->delayedCommandRegistry->addCommand(new EcommerceAddItem($item));
    }
    $this->delayedCommandRegistry->addCommand(new EcommerceSend());
  }

  /**
   * Track the registered Analytics events.
   *
   * @param \Drupal\ga\Event\CollectEvent $event
   *   The AnalyticsEvents::COLLECT event.
   */
  public function trackRegisteredEvents(CollectEvent $event) {
    $commands = $this->delayedCommandRegistry->getCommands();
    foreach ($commands as $command) {
      $event->addCommand($command);
    }
  }

}
