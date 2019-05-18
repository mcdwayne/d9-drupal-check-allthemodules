<?php

namespace Drupal\eloqua_app_cloud\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class OauthValidatorRouteSubscriber.
 *
 * @package Drupal\eloqua_app_cloud\Routing
 * Listens to the dynamic route events.
 */
class OauthValidatorRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.eloqua_app_cloud_service.canonical')) {
      $route->setRequirement('_eloqua_oauth_validate', 'TRUE');
    }
  }

}
