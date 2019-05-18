<?php

namespace Drupal\comment_ip\Routing;

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
    // Change path '/user/login' to '/login'.
    if ($route = $collection->get('comment.admin')) {
      $route->setDefault('_controller', 'Drupal\comment_ip\Controller\CommentIpAdminController::adminPage');
    }
    if ($route = $collection->get('comment.admin_approval')) {
      $route->setDefault('_controller', 'Drupal\comment_ip\Controller\CommentIpAdminController::adminPage');
    }
  }

}
