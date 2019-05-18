<?php

namespace Drupal\external_entities\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes for external entity types.
 */
class ExternalEntityTypeRoutes implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityTypeRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a collection of routes.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A collection of routes.
   */
  public function routes() {
    $collection = new RouteCollection();

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->getProvider() === 'external_entities') {
        // Edit page.
        $route = new Route('/admin/structure/external-entity-types/' . $entity_type_id);
        $route->setDefault('_entity_form', 'external_entity_type.edit');
        $route->setDefault('_title_callback', '\Drupal\Core\Entity\Controller\EntityController::title');
        $route->setDefault('external_entity_type', $entity_type_id);
        $route->setRequirement('_permission', 'administer external entity types');
        $collection->add('entity.external_entity_type.' . $entity_type_id . '.edit_form', $route);

        // Delete page.
        $route = new Route('/admin/structure/external-entity-types/' . $entity_type_id . '/delete');
        $route->setDefault('_entity_form', 'external_entity_type.delete');
        $route->setDefault('_title', 'Delete');
        $route->setDefault('external_entity_type', $entity_type_id);
        $route->setRequirement('_permission', 'administer external entity types');
        $collection->add('entity.external_entity_type.' . $entity_type_id . '.delete_form', $route);
      }
    }

    return $collection;
  }

}
