<?php

namespace Drupal\taxonomy_per_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\gvocab\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // For all the necessary admin routes grant permission
    // (admin/structure/taxonomy).
    if ($route = $collection->get('entity.taxonomy_vocabulary.collection')) {
      $route->setRequirements([
        '_custom_access' => '\taxonomy_per_user_route_access',
      ]);
      $route->setOption('op', 'index');
    }

    // Overview page.
    // admin/structure/taxonomy/manage/{taxonomy_vocabulary}/overview.
    if ($route = $collection->get('entity.taxonomy_vocabulary.overview_form')) {
      $route->setRequirements([
        '_custom_access' => '\taxonomy_per_user_route_access',
      ]);
      $route->setOption('op', 'List');
    }

    // Vocabulary Edit form -
    // admin/structure/taxonomy/manage/{taxonomy_vocabulary}.
    if ($route = $collection->get('entity.taxonomy_vocabulary.edit_form')) {
      $route->setRequirements([
        '_custom_access' => '\taxonomy_per_user_route_access',
      ]);
    }

    // Vocabulary delete - admin/structure/taxonomy/%vocabulary/delete.
    if ($route = $collection->get('entity.taxonomy_vocabulary.delete_form')) {
      $route->setRequirements([
        '_custom_access' => '\taxonomy_per_user_route_access',
      ]);
    }

    // Reset order.
    if ($route = $collection->get('entity.taxonomy_vocabulary.reset_form')) {
      $route->setRequirements([
        '_custom_access' => '\taxonomy_per_user_route_access',
      ]);
    }

    // Overview page.
    // admin/structure/taxonomy/manage/{taxonomy_term}/overview.
    if ($route = $collection->get('entity.taxonomy_term.canonical')) {
      $route->setRequirements([
        '_custom_access' => '\taxonomy_per_user_route_access',
      ]);
      $route->setOption('op', 'List');
    }

    // Term Edit page - taxonomy/term/{taxonomy_term}/edit.
    if ($route = $collection->get('entity.taxonomy_term.edit_form')) {
      $route->setRequirements([
        '_custom_access' => '\taxonomy_per_user_route_access',
      ]);
      $route->setOption('op', 'Edit');
    }

    // Term Create page - taxonomy/term/{taxonomy_term}/edit.
    if ($route = $collection->get('entity.taxonomy_term.add_form')) {
      $route->setRequirements([
        '_custom_access' => '\taxonomy_per_user_route_access',
      ]);
      $route->setOption('op', 'Create');
    }

    // Term delete - taxonomy/term/{taxonomy_term}/delete.
    if ($route = $collection->get('entity.taxonomy_term.delete_form')) {
      $route->setRequirements([
        '_custom_access' => '\taxonomy_per_user_route_access',
      ]);
      $route->setOption('op', 'Delete');
    }

    $route->setOption('op', '');
  }

}
