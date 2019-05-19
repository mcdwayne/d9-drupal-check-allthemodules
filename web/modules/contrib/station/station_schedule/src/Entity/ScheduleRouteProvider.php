<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Entity\ScheduleRouteProvider.
 */

namespace Drupal\station_schedule\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * @todo.
 */
class ScheduleRouteProvider extends DefaultHtmlRouteProvider {

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
    if ($collection_route = $this->getScheduleRoute($entity_type)) {
      $collection->add("{$route_prefix}.schedule", $collection_route);
    }
    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->add("{$route_prefix}.collection", $collection_route);
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
        ->setDefault('_entity_form', "{$entity_type_id}.add")
        ->setDefault('_title', 'Add schedule')
        ->setRequirement('_entity_create_access', $entity_type_id)
        ->setOption('_admin_route', TRUE);
      return $route;
    }
  }

  /**
   * @todo.
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('collection') && $entity_type->hasListBuilderClass()) {
      $route = new Route($entity_type->getLinkTemplate('collection'));
      $route
        ->setDefault('_entity_list', $entity_type->id())
        ->setDefault('_title', 'Schedules')
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);
      return $route;
    }
  }

  /**
   * @todo.
   */
  protected function getScheduleRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('schedule')) {
      $route = new Route($entity_type->getLinkTemplate('schedule'));
      $route
        ->setDefault('_controller', '\Drupal\station_schedule\Controller\AlterScheduleController::alterSchedule')
        ->setDefault('_title', 'Alter schedule')
        ->setRequirement('_entity_access', "{$entity_type->id()}.update")
        ->setOption('_admin_route', TRUE);
      return $route;
    }
  }

}
