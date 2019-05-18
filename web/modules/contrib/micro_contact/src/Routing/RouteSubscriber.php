<?php

namespace Drupal\micro_contact\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add requirement on contact form from an entity site.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add as custom access requirement on contact routes.
    $route = $collection->get('contact.site_page');
    if ($route) {
      $route->addRequirements([
        '_micro_contact_access' => 'TRUE',
      ]);
    }

    $route = $collection->get('entity.contact_form.canonical');
    if ($route) {
      $route->addRequirements([
        '_micro_contact_access' => 'TRUE',
      ]);
    }
  }

}
