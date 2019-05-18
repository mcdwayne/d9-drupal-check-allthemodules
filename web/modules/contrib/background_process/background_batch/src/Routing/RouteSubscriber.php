<?php

namespace Drupal\background_batch\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Implements to Alter Dynamic Routes.
   */
  public function alterRoutes(RouteCollection $collection) {}

}
