<?php

namespace Drupal\purest\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class RemoveXGeneratorHeaderSubscriber.
 */
class RemoveXGeneratorHeaderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.response'] = ['kernelResponse'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.response event is dispatched.
   *
   * Remove X-Generator "Drupal" header from rest responses.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The kernel reponse event.
   */
  public function kernelResponse(Event $event) {
    $response = $event->getResponse();
    $response->headers->remove('X-Generator');
  }

}
