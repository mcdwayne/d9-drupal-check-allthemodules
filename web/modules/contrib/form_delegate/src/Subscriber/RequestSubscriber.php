<?php

namespace Drupal\form_delegate\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Request subscriber.
 */
class RequestSubscriber implements EventSubscriberInterface {

  /**
   * Reacts to the kernel request event.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event object.
   */
  public function onRequest(GetResponseEvent $event) {
    $requestPath = $event->getRequest()->getPathInfo();

    // For performance concerns only execute this code when needed.
    if (strpos($requestPath, '/node/preview/') === FALSE) {
      return;
    }

    // Because the form class can be de-serialized and saved in storage, then
    // loaded on next request (ex: in case of node preview), we have to make
    // sure the class definition already exists. Otherwise we get a PHP
    // incomplete class object.
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $nodeForm = $entityTypeManager->getDefinition('node')->getFormClass('default');
    $entityTypeManager->createFormDelegateClass($nodeForm);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onRequest', 500]];
  }

}
