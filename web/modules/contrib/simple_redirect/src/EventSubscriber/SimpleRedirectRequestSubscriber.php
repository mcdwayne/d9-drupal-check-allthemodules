<?php

namespace Drupal\simple_redirect\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SimpleRedirectRequestSubscriber.
 *
 * @package Drupal\simple_redirect
 */
class SimpleRedirectRequestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['onKernalRequestSimpleRedirect'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onKernalRequestSimpleRedirect(GetResponseEvent $event) {
//    drupal_set_message('Event kernel.request thrown by Subscriber in module simple_redirect.', 'status', TRUE);

    $request = clone $event->getRequest();

    $redirect_url = $this->getRedirectUrl($request->getRequestUri());
    if ($redirect_url && $redirect_url != NULL) {
      $event->setResponse(new RedirectResponse($redirect_url, 301));
    }
  }

  /**
   * Helper to check from url in the config, if exist return redirect url.
   *
   * @param $fromUrl
   * @return \Drupal\Core\GeneratedUrl|null|string
   */
  private function getRedirectUrl($fromUrl) {
    $simple_redirect_conf = \Drupal::entityTypeManager()->getStorage('simple_redirect')->loadMultiple();
    foreach ($simple_redirect_conf as $conf) {
      if ($conf->getFrom() == $fromUrl) {
//      if ("/drupal" . $conf->getFrom() == $fromUrl) {
        return Url::fromUserInput($conf->getTo())->toString();
      }
    }
    return NULL;
  }

}
