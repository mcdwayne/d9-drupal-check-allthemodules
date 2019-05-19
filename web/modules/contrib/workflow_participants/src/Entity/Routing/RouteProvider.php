<?php

namespace Drupal\workflow_participants\Entity\Routing;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Workflow participants entity route provider.
 *
 * Provides the following routes:
 *   - The workflow participants tab for a given entity.
 */
class RouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new DefaultHtmlRouteProvider.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($container->get('entity_field.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    if ($route = $this->getWorkflowParticipantsRoute($entity_type)) {
      $collection->add("entity.{$entity_type->id()}.workflow_participants", $route);
    }

    return $collection;
  }

  /**
   * Gets the workflow participants route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The route item.
   */
  protected function getWorkflowParticipantsRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('workflow-participants')) {
      $route = new Route($entity_type->getLinkTemplate('workflow-participants'));
      $route
        ->addDefaults([
          '_entity_form' => 'workflow_participants',
          '_title_callback' => 'Drupal\Core\Entity\Controller\EntityController::title',
        ])
        ->setRequirement('_workflow_participants_manage_access', 'TRUE')
        ->setOption('_workflow_participants_entity_type', $entity_type->id())
        ->setOption('parameters', [
          $entity_type->id() => [
            'type' => 'entity:' . $entity_type->id(),
          ],
        ]);

      // Enable admin theme when necessary.
      if ($entity_type->id() === 'node') {
        $route->setOption('_node_operation_route', TRUE);
      }
      else {
        $route->setOption('_admin_route', TRUE);
      }

      // Better route matching if the entity type has integer IDs.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type->id(), '\d+');
      }

      return $route;
    }
  }

  /**
   * Gets the type of the ID key for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type.
   *
   * @return string|null
   *   The type of the ID key for a given entity type, or NULL if the entity
   *   type does not support fields.
   *
   * @see \Drupal\content_moderation\Entity\Routing\EntityModerationRouteProvider::getEntityTypeIdKeyType()
   */
  protected function getEntityTypeIdKeyType(EntityTypeInterface $entity_type) {
    if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      return NULL;
    }

    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type->id());
    return $field_storage_definitions[$entity_type->getKey('id')]->getType();
  }

}
