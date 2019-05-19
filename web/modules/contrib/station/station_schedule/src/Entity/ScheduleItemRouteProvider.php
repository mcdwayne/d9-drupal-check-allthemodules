<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Entity\ScheduleItemRouteProvider.
 */

namespace Drupal\station_schedule\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * @todo.
 */
class ScheduleItemRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $route_prefix = "entity.{$entity_type->id()}";
    foreach (["{$route_prefix}.edit_form", "{$route_prefix}.delete_form"] as $name) {
      if ($route = $collection->get($name)) {
        $route->setOption('_admin_route', TRUE);
      }
    }
    if ($collection_route = $this->getAddRoute($entity_type)) {
      $collection->add("{$route_prefix}.add_form", $collection_route);
    }
    return $collection;
  }

  /**
   * @todo.
   */
  protected function getAddRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-form') && $entity_type->getFormClass('add')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('add-form'));
      $route
        ->setDefault('_controller', '\Drupal\station_schedule\Controller\ScheduleItemController::addScheduleItem')
        ->setDefault('_title', 'Add schedule item')
        ->setDefault('start', 0)
        ->setDefault('finish', 60)
        ->setRequirement('_entity_create_access', $entity_type_id)
        ->setOption('_admin_route', TRUE);
      return $route;
    }
  }

}
