<?php

namespace Drupal\enterprise_search\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

class RouteSubscriber extends RouteSubscriberBase
{

    public function alterRoutes(RouteCollection $collection)
    {
        if ($route = $collection->get('search_api.overview')) {
            $route->setDefault('_title', 'Enterprise Search');
        }
    }
}
