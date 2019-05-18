<?php

namespace Drupal\s3fs_file_proxy_to_s3\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\s3fs_file_proxy_to_s3\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @param \Symfony\Component\Routing\RouteCollection $collection
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('s3fs.image_styles')) {
      $route->setPath('/s3fs_to_s3/files/styles/{image_style}/{scheme}');
    }
  }

}
