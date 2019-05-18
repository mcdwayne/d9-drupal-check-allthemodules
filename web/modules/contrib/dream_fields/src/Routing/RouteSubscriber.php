<?php

namespace Drupal\dream_fields\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * A route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
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
      if (!$route_name = $entity_type->get('field_ui_base_route')) {
        continue;
      }
      if (!$entity_route = $collection->get($route_name)) {
        continue;
      }
      $path = $entity_route->getPath();
      $options = $entity_route->getOptions();

      if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
        $options['parameters'][$bundle_entity_type] = [
          'type' => 'entity:' . $bundle_entity_type,
        ];
      }

      $options['_field_ui'] = TRUE;

      $permissions = [
        '_permission' => 'administer ' . $entity_type_id . ' fields,access dream fields',
      ];
      $defaults = [
        'entity_type_id' => $entity_type_id,
      ];
      if (strpos($path, '{bundle}') === FALSE) {
        $defaults['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
      }

      $configure_route = new Route("$path/fields/add-field-simple/{field_type}", [
          '_form' => '\Drupal\dream_fields\Form\ConfigureField',
          '_title_callback' => '\Drupal\dream_fields\Form\ConfigureField::title',
        ] + $defaults,
        $permissions,
        $options
      );
      $add_route = new Route("$path/fields/add-field-simple", [
          '_controller' => '\Drupal\dream_fields\Controller\AddField::addField',
          '_title_callback' => '\Drupal\dream_fields\Controller\AddField::title',
        ] + $defaults,
        $permissions,
        $options
      );

      $collection->add("dream_fields.configure_field_$entity_type_id", $configure_route);
      $collection->add("dream_fields.add_field_$entity_type_id", $add_route);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -90];
    return $events;
  }

}
