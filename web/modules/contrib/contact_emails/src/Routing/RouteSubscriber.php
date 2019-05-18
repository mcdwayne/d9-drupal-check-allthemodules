<?php

namespace Drupal\contact_emails\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscribe to the Route to change page titles.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Create new contact email form.
    if ($route = $collection->get('entity.contact_email.add_form')) {
      $route->setDefault('_title_callback', '\Drupal\contact_emails\Routing\RouteCallback::addFormTitle');
    }

    // Edit contact emailform.
    if ($route = $collection->get('entity.contact_email.edit_form')) {
      $route->setDefault('_title_callback', '\Drupal\contact_emails\Routing\RouteCallback::editFormTitle');
    }
  }

}
