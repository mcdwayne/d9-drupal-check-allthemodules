<?php

namespace Drupal\taxonomy_entity\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets the _form for overview terms pages.
 */
class TaxonomyRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.taxonomy_vocabulary.overview_form')) {
      $route->setDefaults(['_form' => '\Drupal\taxonomy_entity\Form\OverviewTerms']);
    }
  }

}
