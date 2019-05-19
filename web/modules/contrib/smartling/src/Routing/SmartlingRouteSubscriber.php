<?php

/**
 * @file
 * Contains \Drupal\config_translation\Routing\RouteSubscriber.
 */

namespace Drupal\smartling\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class SmartlingRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->manager->getDefinitions() as $entity_type_id => $entity_type) {
      $route_name = 'entity.' . $entity_type_id . '.canonical';
      // @todo Rebuild routes when translation changed.
      if ($entity_type->isTranslatable() && $entity_type->getLinkTemplate('canonical') && ($entity_route = $collection->get($route_name))) {
        // Inherit admin route status from edit route, if exists.
        $is_admin = FALSE;
        $route_name = "entity.$entity_type_id.edit_form";
        if ($edit_route = $collection->get($route_name)) {
          $is_admin = (bool) $edit_route->getOption('_admin_route');
        }
        // Add smartling route to entity with canonical link template.
        $route = new Route(
          $entity_route->getPath() . '/smartling',
          [
            '_form' => '\Drupal\smartling\Form\EntitySubmissionsForm',
            '_title_callback' => '\Drupal\smartling\Controller\SmartlingController::submissionsTitle',
            'entity_type_id' => $entity_type_id,
          ],
          [
            // @todo Implement special access checker.
            '_entity_access' => $entity_type_id . '.edit',
            '_access_content_translation_overview' => $entity_type_id,
            '_permission' => 'use smartling entity translation',
          ],
          [
            'parameters' => [
              $entity_type_id => [
                'type' => 'entity:' . $entity_type_id,
              ]
            ],
            '_admin_route' => $is_admin,
          ]
        );
        $collection->add("entity.{$entity_type_id}.smartling", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Come after content_translation (-110).
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -120);
    return $events;
  }

}
