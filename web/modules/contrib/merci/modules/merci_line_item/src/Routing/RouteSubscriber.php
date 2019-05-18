<?php

namespace Drupal\merci_line_item\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change path '/user/login' to '/login'.
    if ($route = $collection->get('views_bulk_operations.execute_configurable')) {
      $route->setDefault('_form', '\Drupal\merci_line_item\Form\MerciConfigureAction');
    }
  }

}
