<?php

namespace Drupal\aws_cloud\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class AwsCloudRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('aws_cloud.instance_type_prices')) {
      $config = \Drupal::config('aws_cloud.settings');

      // If the configuration is false, the page can't be accessed.
      if ($config->get('aws_cloud_instance_type_prices') == FALSE) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
