<?php

namespace Drupal\eloqua_app_cloud\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ContentSecurityPolicySubscriber.
 *
 * @package Drupal\eloqua_app_cloud
 */
class ContentSecurityPolicySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.response'] = ['setHeaderContentSecurityPolicy'];
    return $events;
  }

  /**
   * This method is called whenever the kernel.response event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function setHeaderContentSecurityPolicy(Event $event) {
    // We only need to respond if the request is coming in to an Eloqua hook.
    if (strpos($event->getRequest()->getPathInfo(), '/eloqua/hook/') === FALSE) {
      return;
    }

    // Remove the X-Frame-Options header and allow *.eloqua.com to iframe us in.
    $response = $event->getResponse();
    $response->headers->remove('X-Frame-Options');
    $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' eloqua.com *.eloqua.com", FALSE);
  }

}
