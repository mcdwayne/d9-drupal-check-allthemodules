<?php

namespace Drupal\markjs_search\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Define the MarkJS profile route provider default.
 */
class MarkjsProfileRouteProviderDefault extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCollectionRoute($entity_type)) {
      $route->setDefault('_title', '@label');
      return $route;
    }
  }
}
