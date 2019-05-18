<?php

namespace Drupal\remove_http_headers\EventSubscriber;

use Drupal\remove_http_headers\Config\ConfigManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Helper service to remove configured HTTP headers.
 */
class RemoveResponseHeaders implements EventSubscriberInterface {

  /**
   * The config manager service.
   *
   * @var \Drupal\remove_http_headers\Config\ConfigManager
   */
  private $configManager;

  /**
   * RemoveResponseHeaders constructor.
   *
   * @param \Drupal\remove_http_headers\Config\ConfigManager $configManager
   *   The config manager service.
   */
  public function __construct(ConfigManager $configManager) {
    $this->configManager = $configManager;
  }

  /**
   * Remove configured HTTP headers.
   */
  public function removeConfiguredHttpHeaders(FilterResponseEvent $event) {
    $response = $event->getResponse();

    foreach ($this->configManager->getHeadersToRemove() as $httpHeaderToRemove) {
      $response->headers->remove($httpHeaderToRemove);
    }
  }

  /**
   * Subscribe to response events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['removeConfiguredHttpHeaders', -1000];

    return $events;
  }

}
