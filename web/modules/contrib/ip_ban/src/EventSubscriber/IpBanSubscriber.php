<?php

/**
 * @file
 * Contains \Drupal\ip_ban\EventSubscriber\IpBanSubscriber.
 */

namespace Drupal\ip_ban\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ip_ban\IpBanSetBanInterface;

class IpBanSubscriber implements EventSubscriberInterface {
  
  /**
   * The IP ban manager.
   *
   * @var \Drupal\ip_ban\IpBanSetBanInterface
   */
  protected $iPBanManager;

  /**
   * Constructs a BanMiddleware object.
   *
   * @param \Drupal\ban\IpBanSetBanInterface $manager
   *   The IP Ban manager.
   */
  public function __construct(IpBanSetBanInterface $manager) {
    $this->iPBanManager = $manager;
  }
   
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('hookInit', 255);
    return $events;
  }
  
  /**
   * Sets the ban value and action for authenticated users.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function hookInit(GetResponseEvent $event) {
    // \Drupal::service("router.builder")->rebuild();    
    $this->iPBanManager->iPBanSetValue();
    $this->iPBanManager->iPBanDetermineAction();
  }
  
}