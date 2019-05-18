<?php

namespace Drupal\block_placeholder\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Define block placeholder routing provider.
 */
class BlockPlaceholderRoutingProvider extends DefaultHtmlRouteProvider {

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


