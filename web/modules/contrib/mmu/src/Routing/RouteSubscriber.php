<?php

namespace Drupal\mmu\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Builds up the routes of all views.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.modules_list')) {
      $route->setDefault('_form', 'Drupal\mmu\Form\ModulesListForm');
      $collection->add('system.modules_list', $route);
    }
  }

}
