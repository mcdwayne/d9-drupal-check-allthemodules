<?php

namespace Drupal\contact_default_fields_override\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\contact_default_fields_override\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('entity.contact_message.field_ui_fields');

    if ($route) {
      $route->setDefault('_controller', '\Drupal\contact_default_fields_override\Controller\ContactMessageFieldConfigListController::listing');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Make sure this runs last so entity.contact_message.field_ui_fields
    // is available.
    $events[RoutingEvents::ALTER] = [
      'onAlterRoutes',
      -1000,
    ];
    return $events;
  }

}
