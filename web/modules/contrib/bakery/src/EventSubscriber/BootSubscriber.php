<?php

namespace Drupal\bakery\EventSubscriber;

/**
 * @file
 * For Boot event subscribe.
 */

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\bakery\BakeryService;

/**
 * For handling chocolatechip cookie on boot.
 */
class BootSubscriber implements EventSubscriberInterface {

  protected $bakeryService;

  /**
   * Initilizing bakeryService.
   *
   * @param object \Drupal\bakery\BakeryService $bakeryService
   *   Bakery service used.
   */
  public function __construct(BakeryService $bakeryService) {
    $this->bakeryService  = $bakeryService;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Should be called on cached pages also.
    return [KernelEvents::REQUEST => ['onEvent', 27]];
  }

  /**
   * On boot event we need to test the cookie.
   */
  public function onEvent(GetResponseEvent $event) {
    // error_log("Here we testing cookie", 0);.
    $this->bakeryService->tasteChocolatechipCookie();
  }

}
