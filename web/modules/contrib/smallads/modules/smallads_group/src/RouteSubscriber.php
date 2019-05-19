<?php

namespace Drupal\smallads_group;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber to change the .
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add the group to the smallad create path, and change the permission
    $route = $collection->get('entity.smallad.add_form');
    $route->setpath('/group/{group}/ad/add/{smallad_type}');
    $route->setRequirements([
        '_group_permission' => 'create-edit-delete own smallads',
        '_method' => 'GET|POST'
    ]);
    $params = $route->getOption('parameters');
    $params['group'] = ['type' => 'entity:group'];
    $route->setOption('parameters', $params);

    // Change permission of the smallad view path.
    // Must be in the same group OR have Drupal permission to 'post smallad'
    $route = $collection->get('entity.smallad.canonical');
  }

}
