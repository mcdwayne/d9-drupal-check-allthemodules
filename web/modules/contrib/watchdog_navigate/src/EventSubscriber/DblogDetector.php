<?php

namespace Drupal\watchdog_navigate\EventSubscriber;

use Drupal\Core\Link;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class DblogDetector.
 */
class DblogDetector implements EventSubscriberInterface {


  /**
   * Constructs a new DblogDetector object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onViewDetectDblog', 1];

    return $events;
  }

  /**
   * This method is called whenever the kernel.view event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onViewDetectDblog(GetResponseForControllerResultEvent $event) {
    $dblog_route = 'dblog.event';
    $request = $event->getRequest();
    $route = $request->get(RouteObjectInterface::ROUTE_NAME);
    if ($route == $dblog_route) {
      $result = $event->getControllerResult();
      $current_id = $request->get('event_id');
      $items = [
        Link::createFromRoute(t('Previous message'), $dblog_route, [
          'event_id' => $current_id - 1,
        ]),
        Link::createFromRoute(t('Next message'), $dblog_route, [
          'event_id' => $current_id + 1,
        ]),
      ];
      $result['navigate'] = [
        '#theme' => 'item_list',
        '#items' => $items,
      ];
      $event->setControllerResult($result);
    }
  }

}
