<?php

namespace Drupal\agcobcau;

use Drupal\Core\PhpStorage\PhpStorageFactory;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AgcobcauSubscriber implements EventSubscriberInterface {

  /**
   * Register the autoloader.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $autoloader = new AgcobcauAutoloader(PhpStorageFactory::get('agcobcau'));
    spl_autoload_register(array($autoloader, 'autoload'), TRUE, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest', 0);
    return $events;
  }

}
