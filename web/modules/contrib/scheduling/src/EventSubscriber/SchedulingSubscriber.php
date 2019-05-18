<?php

namespace Drupal\scheduling\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SchedulingSubscriber implements EventSubscriberInterface {

  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();

    // We only modify cacheable responses.
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    $this->modifyCacheHeaders($response);
  }

  private function modifyCacheHeaders($response) {

    if ($expiries =& drupal_static('scheduling_expires', false)) {

      if ($expiries) {
        /** @var \Drupal\Component\Datetime\DateTimePlus $then */
        $then = new DrupalDateTime('now', new \DateTimeZone('GMT'));
        $then->modify('+' .  min($expiries) . ' seconds');

        /** @var CacheableResponseInterface $response */
        $response->headers->addCacheControlDirective('max-age', min($expiries));
        $response->headers->set('Expires', $then->format('D, d M Y H:i:s \G\M\T'));
        $response->headers->set('X-Expires', $then->format('D, d M Y H:i:s \G\M\T'));
        $response->headers->set('X-Expires-Timestamp', $then->getTimestamp());
      }
    }

  }

  /**
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    // Respond after FinishResponseSubscriber by setting low priority.
    $events[KernelEvents::RESPONSE][] = array('onRespond', -1024);
    return $events;
  }

}
