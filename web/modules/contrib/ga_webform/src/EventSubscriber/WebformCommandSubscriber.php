<?php

namespace Drupal\ga_webform\EventSubscriber;

use Drupal\ga\AnalyticsEvents;
use Drupal\ga\Event\CollectEvent;
use Drupal\ga_webform\DelayedCommandRegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sending Googalytics tracking commands for Webform submissions.
 */
class WebformCommandSubscriber implements EventSubscriberInterface {

  /**
   * The delayed command registry.
   *
   * @var \Drupal\ga_webform\DelayedCommandRegistryInterface
   */
  protected $delayedCommandRegistry;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      AnalyticsEvents::COLLECT => ['trackRegisteredSubmissions'],
    ];
    return $events;
  }

  /**
   * Constructs a new WebformCommandSubscriber object.
   *
   * @param \Drupal\ga_webform\DelayedCommandRegistryInterface $delayed_command_registry
   *   The delayed command registry service.
   */
  public function __construct(DelayedCommandRegistryInterface $delayed_command_registry) {
    $this->delayedCommandRegistry = $delayed_command_registry;
  }

  /**
   * Add registered commands for Webform submissions.
   *
   * @param \Drupal\ga\Event\CollectEvent $event
   *   The AnalyticsEvents::COLLECT event.
   */
  public function trackRegisteredSubmissions(CollectEvent $event) {
    $commands = $this->delayedCommandRegistry->getCommands();
    foreach ($commands as $command) {
      $event->addCommand($command);
    }
  }

}
