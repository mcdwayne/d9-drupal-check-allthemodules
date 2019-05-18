<?php

namespace Drupal\entity_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines Entity UI actions on the admin collections.
 *
 * This hands over to entity types' entity_ui_admin handler, to adds a local
 * action to add a new Entity Tab to each of the Entity Tab admin UI collections
 * on different entity types.
 */
class EntityTabsAdminLocalActions extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an SelectionBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\Query\QueryInterface $tab_query
   *   The entity query object for entity tab entities.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_types = $this->entityTypeManager->getDefinitions();

    // Get derivative definitions from each entity type's entity_ui_admin
    // handler.
    foreach ($entity_types as $target_entity_type_id => $target_entity_type) {
      if ($this->entityTypeManager->hasHandler($target_entity_type_id, 'entity_ui_admin')) {
        $entity_ui_admin_handler = $this->entityTypeManager->getHandler($target_entity_type_id, 'entity_ui_admin');

        $this->derivatives += $entity_ui_admin_handler->getLocalActions($base_plugin_definition);
      }
    }

    return $this->derivatives;
  }

}
