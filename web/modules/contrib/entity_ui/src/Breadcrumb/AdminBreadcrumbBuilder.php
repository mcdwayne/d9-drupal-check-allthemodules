<?php

namespace Drupal\entity_ui\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\system\PathBasedBreadcrumbBuilder;

/**
 * Breadcrumb provider for the EntityTab edit form.
 *
 * This uses the same route for tabs on all target entity types, therefore
 * needs its breadcrumb to make it appear below the target entity type's tab
 * collection.
 *
 * We inherit from \Drupal\system\PathBasedBreadcrumbBuilder\ because we can't
 * effectively call it as a service; see https://www.drupal.org/node/2884217
 * for details.
 */
class AdminBreadcrumbBuilder extends PathBasedBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
  * {@inheritdoc}
  */
  public function applies(RouteMatchInterface $route_match) {
    return ($route_match->getRouteName() == 'entity.entity_tab.edit_form');
  }

  /**
  * {@inheritdoc}
  */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    // Get the entity tab out of the route match to find its target entity type.
    $entity_tab = $route_match->getParameter('entity_tab');
    $target_entity_type_id = $entity_tab->getTargetEntityTypeID();

    // The route name for the entity tab collection on a target entity type is
    // standard: see
    // \Drupal\entity_ui\EntityHandler\EntityUIAdminBase::getRoutes().
    $target_entity_collection_route_name = "entity_ui.entity_tab.{$target_entity_type_id}.collection";
    $target_entity_collection_route = \Drupal::service('router')->getRouteCollection()->get($target_entity_collection_route_name);

    // Get a route match for the collection route.
    $collection_route_match = new RouteMatch($target_entity_collection_route_name, $target_entity_collection_route);

    // At this point, we *ought* to be able to piggyback off
    // system.breadcrumb.default:build() to get the breadcrumb for the target
    // entity type's tab collection route, but that doesn't respect the
    // $route_match parameter it's passed.
    // See https://www.drupal.org/node/2884217.

    // Instead, repeat the work ourselves. Because we inherit from it, we can
    // at least rely on its helper methods.
    // For now, assume that the collection route won't have any placeholders.
    $collection_route_path = $target_entity_collection_route->getPath();
    $path = trim($collection_route_path, '/');
    $path_elements = explode('/', $path);

    $breadcrumb->addCacheContexts(['url.path.parent']);
    while (count($path_elements) > 1) {
      array_pop($path_elements);
      // Copy the path elements for up-casting.
      $route_request = $this->getRequestForPath('/' . implode('/', $path_elements), []);
      if ($route_request) {
        $route_match = RouteMatch::createFromRequest($route_request);
        $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
        // The set of breadcrumb links depends on the access result, so merge
        // the access result's cacheability metadata.
        $breadcrumb = $breadcrumb->addCacheableDependency($access);
        if ($access->isAllowed()) {
          $title = $this->titleResolver->getTitle($route_request, $route_match->getRouteObject());
          if (!isset($title)) {
            // Fallback to using the raw path component as the title if the
            // route is missing a _title or _title_callback attribute.
            $title = str_replace(['-', '_'], ' ', Unicode::ucfirst(end($path_elements)));
          }
          $url = Url::fromRouteMatch($route_match);
          $links[] = new Link($title, $url);
        }
      }

    }

    // Add the Home link.
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');

    // Reverse the links.
    $links = array_reverse($links);

    // Add the tab collection link.
    $links[] = Link::createFromRoute($this->t('Entity tabs'), $target_entity_collection_route_name);

    return $breadcrumb->setLinks($links);
  }

}
