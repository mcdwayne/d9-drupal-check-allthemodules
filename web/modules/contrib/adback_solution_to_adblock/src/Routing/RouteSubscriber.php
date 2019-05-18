<?php

namespace Drupal\adback_solution_to_adblock\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber
 */
class RouteSubscriber extends RouteSubscriberBase
{
    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection)
    {
        $config = \Drupal::config('adback_solution_to_adblock.endpoints');

        foreach (['old_end_point', 'end_point', 'next_end_point'] as $endpointType) {
            if ($route = $collection->get('adback_solution_to_adblock.' . $endpointType)) {
                $route->setPath('/' . $config->get($endpointType) . '/{proxyfiedData}');
            }
        }
    }
}
