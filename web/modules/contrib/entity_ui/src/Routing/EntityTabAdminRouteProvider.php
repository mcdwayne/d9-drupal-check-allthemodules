<?php

namespace Drupal\entity_ui\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\entity_ui\Controller\EntityTabAddPage;
use Symfony\Component\Routing\Route;

/**
 * Provides admin routes for Entity tab entities.
 *
 * The collection route is disabled here, as multiple collection routes are
 * instead provided by \Drupal\entity_ui\Routing\AdminRouteProviderSubscriber.
 */
class EntityTabAdminRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    // Do nothing: each target entity type has its own collection route,
    // provided by AdminRouteProviderSubscriber.
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    // Need to override this, since our entity type doesn't have bundles.
    if ($entity_type->hasLinkTemplate('add-page')) {
      $route = new Route($entity_type->getLinkTemplate('add-page'));
      $route->setDefault('_controller', EntityTabAddPage::class . '::content');
      $route->setDefault('_title_callback', EntityTabAddPage::class . '::title');
      $route->setRequirement('_entity_create_access', $entity_type->id());

      return $route;
    }
  }

}
