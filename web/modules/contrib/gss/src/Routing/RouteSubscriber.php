<?php

namespace Drupal\gss\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\gss\Plugin\Search\Search as GssSearch;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {

    // Alter search page controller for this module's plugin.
    /** @var \Drupal\search\SearchPageRepositoryInterface $searchPageRepository */
    $searchPageRepository = \Drupal::service('search.search_page_repository');
    foreach ($searchPageRepository->getActiveSearchPages() as $entity_id => $entity) {
      if ($entity->getPlugin() instanceof GssSearch &&
          $route = $collection->get("search.view_$entity_id")
      ) {
        $route->setDefault('_controller',
          'Drupal\gss\Controller\GssSearchController::view');
      }
    }
  }

}
