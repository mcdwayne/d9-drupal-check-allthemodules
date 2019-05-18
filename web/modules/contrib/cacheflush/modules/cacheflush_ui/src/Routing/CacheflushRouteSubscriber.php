<?php

namespace Drupal\cacheflush_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * A route subscriber to remove routes that depend on modules being enabled.
 */
class CacheflushRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('cacheflush.presets.clear_id')) {
      $route->setRequirement('_entity_access', 'cacheflush.clear');
      $collection->add('cacheflush.presets.clear_id', $route);
    }
  }

}
