<?php

namespace Drupal\mass_contact\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Route provider for mass contact message entities.
 */
class HtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    $route->setDefault('_title_callback', NULL);
    $route->setDefault('_title', 'Mass Contact');
    return $route;
  }

}
