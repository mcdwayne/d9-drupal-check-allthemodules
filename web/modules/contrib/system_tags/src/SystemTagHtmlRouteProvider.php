<?php

namespace Drupal\system_tags;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Class SystemTagHtmlRouteProvider.
 *
 * @package Drupal\system_tags\SystemTagListBuilder
 */
class SystemTagHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    if ($route) {
      $route->setOption('_admin_route', TRUE);
    }

    return $route;
  }

}
