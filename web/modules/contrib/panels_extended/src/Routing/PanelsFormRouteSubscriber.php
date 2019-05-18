<?php

namespace Drupal\panels_extended\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Modify the routes for the add and edit panels block forms.
 */
class PanelsFormRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('panels.add_block')) {
      $route->setDefault('_form', 'Drupal\panels_extended\Form\ExtendedPanelsAddForm');
    }
    if ($route = $collection->get('panels.edit_block')) {
      $route->setDefault('_form', 'Drupal\panels_extended\Form\ExtendedPanelsEditForm');
    }
  }

}
