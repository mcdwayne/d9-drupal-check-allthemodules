<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\EventSubscriber\CacheableResponseSubscriber.
 */

namespace Drupal\adv_varnish\EventSubscriber;

use Drupal\adv_varnish\Controller\AdvVarnishController;
use Drupal\Core\Annotation\Action;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\adv_varnish\AdvVarnishInterface;

/**
 * Event subscriber class.
 */
class CacheableResponseSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(AdvVarnishController $controller) {
    $this->controller = $controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onResponse');
    return $events;
  }

  /**
   * Response event handler.
   *
   * @param FilterResponseEvent $event
   *
   * Process CacheableResponse.
   */
  public function onResponse(FilterResponseEvent $event) {
    $this->controller->handleResponseEvent($event);
  }

}
