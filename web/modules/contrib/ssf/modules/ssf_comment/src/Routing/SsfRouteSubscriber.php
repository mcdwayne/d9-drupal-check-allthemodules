<?php

namespace Drupal\ssf_comment\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for routes.
 */
class SsfRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('comment.approve')) {
      $route->setDefault('_controller', '\Drupal\ssf_comment\Controller\SsfCommentController::commentApprove');
    }
  }

}
