<?php

namespace Drupal\big_pipe_override\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\big_pipe_override\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if (Settings::get('big_pipe_override_enabled')) {
      foreach ($collection->getIterator() as $route) {
        $route->setOption('_no_big_pipe', TRUE);
      }
    }
  }

}
