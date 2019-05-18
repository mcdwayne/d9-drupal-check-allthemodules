<?php

namespace Drupal\maintenance200\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class MaintenanceModeSubscriber implements EventSubscriberInterface {

  public function onKernelResponse(FilterResponseEvent $event) {
  	if (config('maintenance200.settings')->get('maintenance200_enabled')) {
  	  $status_code = config('maintenance200.settings')->get('maintenance200_status_code');
	    $request = $event->getRequest();
	    $response = $event->getResponse();
	    if ($request->attributes->get('_maintenance') == MENU_SITE_OFFLINE) {
		    if (is_numeric($status_code)) {
		      $response->setStatusCode($status_code);
		      $event->setResponse($response);
		    }
	    }
    }
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onKernelResponse', 31);
    return $events;
  }

}
