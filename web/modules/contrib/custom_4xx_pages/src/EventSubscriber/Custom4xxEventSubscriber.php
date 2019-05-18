<?php

namespace Drupal\custom_4xx_pages\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Custom4xxEventSubscriber.
 *
 * @package Drupal\custom_4xx_pages
 */
class Custom4xxEventSubscriber implements EventSubscriberInterface {
  
  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.response'] = ['kernel_response'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.response event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function kernel_response(Event $event) {
    // Here's the logic to check if the path request is 
    // a '/node' path. This means the user is attempting
    // to access a direct /node path, which we don't want.
    // So, we redirect to generic system 403 if this is
    // the case (the user is not authenticated, we could
    // easily introduce a permission or role check here 
    // if needed.)
    // $requested_path = \Drupal::service('request_stack')->getMasterRequest()->getRequestUri();
    // $path_args = explode('/', trim($requested_path, '/'));
    // if ($path_args[0] == 'node') {
    //   $raw_user = \Drupal::currentUser();
    //   $user_is_authed = $raw_user->isAuthenticated();
    //   if (!$user_is_authed) {
    //     // $redirect_response = new RedirectResponse("http://-2.local/system/403");
    //     $response->setContent($redirect_response);
    //     ksm($response->headers);

    //     $response->send();
    //   }
    // }
  }

}
