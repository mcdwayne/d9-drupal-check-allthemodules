<?php

namespace Drupal\private_taxonomy\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.taxonomy_vocabulary.collection')) {
      $requirements = [
        '_private_taxonomy_view' => 'vocabulary_list',
      ];
      $route->setRequirements($requirements);
    }
    if ($route = $collection->get('entity.taxonomy_vocabulary.overview_form')) {
      $requirements = [
        '_private_taxonomy_view' => 'vocabulary_list',
      ];
      $route->setRequirements($requirements);
    }
  }

}
