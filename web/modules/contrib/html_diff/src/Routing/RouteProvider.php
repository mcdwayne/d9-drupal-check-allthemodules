<?php
/**
 * @file
 * Contains \Drupal\html_diff\Routing\RouteProvider.
 */

namespace Drupal\html_diff\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\diff\Routing\DiffRouteProvider;
use Drupal\html_diff\Controller\HtmlDiffGenericRevisionController;

class RouteProvider extends DiffRouteProvider{

  /**
   * {@inheritdoc}
   */
  protected function getDiffRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revisions-diff')) {
      $route = parent::getDiffRoute($entity_type);
      $route->addDefaults([
        '_controller' => '\Drupal\html_diff\Controller\HtmlDiffGenericRevisionController::compareEntityRevisions',
        'filter' => HtmlDiffGenericRevisionController::FILTER,
      ]);
      return $route;
    }
  }

}
