<?php

namespace Drupal\ip_ranges\EventSubscriber;

use Drupal\ip_ranges\IPRangesManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IPRangesEventSubscriber implements EventSubscriberInterface {

  protected $request;

  protected $manager;

  public function __construct(Request $request, IPRangesManager $manager) {
    $this->request = $request;
    $this->manager = $manager;
  }

  public function onKernelRequest(GetResponseEvent $event) {
    if ($this->manager->ipIsBanned($this->request->getClientIp())) {
      $event->stopPropagation();
      $response = new Response((t('Sorry, @ip has been banned.', array('@ip' => $this->request->getClientIp()))), 403);
      $event->setResponse($response);
    }
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest', 100);
    return $events;
  }

}
