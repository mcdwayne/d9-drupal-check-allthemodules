<?php

namespace Drupal\entity_delete_op\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines a route subscriber for altering entity routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new instance of RouteSubscriber.
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
  public function alterRoutes(RouteCollection $collection) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types = $this->getSupportedEntityTypes();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type->get('entity_delete_op')) {
        continue;
      }

      // Alter the assumed traditional entity delete route so that it is updated
      // to utilize our delete confirmation form instead. This will provide
      // coverage to assist in ensuring the operation is simply marking the
      // entity as deleted instead of purging from persistent storage.
      $route = $collection->get("entity.$entity_type_id.delete_form");
      if (!empty($route)) {
        $defaults = $route->getDefaults();
        // Remove the assumed `_entity_form` property in place of ours form.
        unset($defaults['_entity_form']);
        $defaults['_controller'] = '\Drupal\entity_delete_op\Controller\DeleteController::deleteEntity';
        $route->setDefaults($defaults);
        $route->setOption('parameters', [
          $entity_type_id => [
            'type' => 'entity:' . $entity_type_id
          ],
        ]);
      }
    }
  }

  /**
   * Returns an array of supported entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity type objects.
   */
  protected function getSupportedEntityTypes() {
    $entity_types = [];
    $definitions = $this->entityTypeManager->getDefinitions();
    foreach ($definitions as $entity_type_id => $entity_type) {
      if ($entity_type->get('entity_delete_op')) {
        $entity_types[$entity_type_id] = $entity_type;
      }
    }
    return $entity_types;
  }

}
