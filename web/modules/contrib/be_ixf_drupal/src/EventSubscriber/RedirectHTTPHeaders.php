<?php
/**
 * @file
 * Contains \Drupal\be_ixf_drupal\EventSubscriber\RedirectHTTPHeaders.
 * @see https://github.com/chapter-three/http_response_headers
 */

namespace Drupal\be_ixf_drupal\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Cache\CacheableResponseInterface;

/**
 * Provides RedirectHTTPHeaders.
 */
class RedirectHTTPHeaders implements EventSubscriberInterface {

  public function onRequest(FilterResponseEvent $event) {
    return;
  }

  /**
   * Sets extra HTTP headers.
   */
  public function onRespond(FilterResponseEvent $event) {
   if (!$event->isMasterRequest()) {
       return;
    }
     
    $response = $event->getResponse();

    $node = \Drupal::routeMatch()->getParameter('node');
    // only apply for nodes not admin or user
    if (!isset($node)) {
      return;

    }

    $max_cache_age = 3600;
    $module_config = \Drupal::config('be_ixf_drupal.settings');  
    if ($module_config->get('block_cache_max_age') != null) {
      $max_cache_age = intval($module_config->get('block_cache_max_age'));
    }

    $expire_time = time() + $max_cache_age;
    $cid = 'be_ixf:redirect:node:' . $node->id();
    $redirect_code = NULL;
    $redirect_location = NULL;
    $cache = \Drupal::cache()->get($cid);

    if ($cache) {
      $data = $cache->data;
//echo "SYU found cache $cid\n";
//var_dump($data);
      if ($data[0]) {
        $redirect_code = $data[1];
        $redirect_location = $data[2];
      }
    } else {
      $be_ixf_client = \Drupal::service("brightedge.request")->getClient();
      if ($be_ixf_client->hasRedirectNode()) {
        $redirect_info = $be_ixf_client->getRedirectNodeInfo();
        if ($redirect_info != null) {
          $redirect_code = $redirect_info[0];
          $redirect_location = $redirect_info[1];
          $data = array(true, $redirect_code, $redirect_location);
        } else {
          $data = array(false);
        }
      }

//echo "SYU set cid $cid\n";
//var_dump($data);

      if (isset($data)) {
        \Drupal::cache()->set($cid, $data, $expire_time);
      }
    }

    if (isset($redirect_code) && isset($redirect_location)) {
      $response->setStatusCode($redirect_code);
      $response->headers->set("Location", $redirect_location);
    }

  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
//    $events[KernelEvents::RESPONSE][] = ['onRequest'];
    return $events;
  }

}
