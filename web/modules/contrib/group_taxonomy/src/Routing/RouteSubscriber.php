<?php

namespace Drupal\group_taxonomy\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber
 *
 * @package Drupal\group_taxonomy\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @param \Symfony\Component\Routing\RouteCollection $collection
   */
  public function alterRoutes(RouteCollection $collection) {
    // For all the necessary admin routes grant permission
    // (admin/structure/taxonomy).
    if ($route = $collection->get('entity.taxonomy_vocabulary.collection')) {
      $route->setRequirements([
        '_custom_access' => '\group_taxonomy_route_access',
      ]);
      $route->setOption('op', 'index');
    }

    // Overview page.
    // admin/structure/taxonomy/manage/{taxonomy_vocabulary}/overview.
    if ($route = $collection->get('entity.taxonomy_vocabulary.overview_form')) {
      $route->setRequirements([
        '_custom_access' => '\group_taxonomy_route_access',
      ]);
      $route->setOption('op', 'list terms');
    }

    // Vocabulary Edit form -
    // admin/structure/taxonomy/manage/{taxonomy_vocabulary}.
    if ($route = $collection->get('entity.taxonomy_vocabulary.edit_form')) {
      $route->setRequirements([
        '_custom_access' => '\group_taxonomy_route_access',
      ]);
    }

    // Vocabulary delete - admin/structure/taxonomy/%vocabulary/delete.
    if ($route = $collection->get('entity.taxonomy_vocabulary.delete_form')) {
      $route->setRequirements([
        '_custom_access' => '\group_taxonomy_route_access',
      ]);
    }

    // Reset order.
    if ($route = $collection->get('entity.taxonomy_vocabulary.reset_form')) {
      $route->setRequirements([
        '_custom_access' => '\group_taxonomy_route_access',
      ]);
    }

    // Term Edit page - taxonomy/term/{taxonomy_term}/edit.
    if ($route = $collection->get('entity.taxonomy_term.edit_form')) {
      $route->setRequirements([
        '_custom_access' => '\group_taxonomy_route_access',
      ]);
      $route->setOption('op', 'edit terms');
    }

    // Term Create page - taxonomy/term/{taxonomy_term}/edit.
    if ($route = $collection->get('entity.taxonomy_term.add_form')) {
      $route->setRequirements([
        '_custom_access' => '\group_taxonomy_route_access',
      ]);
      $route->setOption('op', 'create terms');
    }

    // Term delete - taxonomy/term/{taxonomy_term}/delete.
    if ($route = $collection->get('entity.taxonomy_term.delete_form')) {
      $route->setRequirements([
        '_custom_access' => '\group_taxonomy_route_access',
      ]);
      $route->setOption('op', 'delete terms');
    }

    // custom AutoComplete
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\group_taxonomy\Controller\GroupTermAutocompleteController::handleAutocomplete');
    }

    $route->setOption('op', '');
  }

}
