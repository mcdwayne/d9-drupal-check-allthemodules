<?php
/**
 * Created by PhpStorm.
 * User: mike
 * Date: 09.06.17
 * Time: 11:40
 */

namespace Drupal\media_entity_usage\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection) {
    $media_type = \Drupal::entityTypeManager()->getDefinition('media');
    if ($route = $this->getMediaUsageRefsRoute($media_type)) {
      $collection->add("entity.media.media_usage_refs", $route);
    }
  }

  protected function getMediaUsageRefsRoute(EntityTypeInterface $entity_type) {
    if ($media_usage_refs = $entity_type->getLinkTemplate('media-usage-refs')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($media_usage_refs);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\media_entity_usage\Controller\MediaUsageController::referencesPage',
          '_title_callback' => '\Drupal\media_entity_usage\Controller\MediaUsageController::referencesTitle',
        ])
        ->addRequirements([
          '_permission' => 'access media overview',
          'media' => "\d+",
        ])
        ->setOption('_admin_route', true);
      return $route;
    }
  }
}