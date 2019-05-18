<?php

namespace Drupal\field_tools;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Contains methods for cloning displays.
 */
class DisplaySettingsCopier {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new FieldCloner.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $entity_query;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Copies the display settings for a field to another bundle.
   *
   * @param FieldDefinitionInterface $field_definition
   *  The field definition.
   * @param EntityDisplayBase $source_entity_display
   *  The source display, view or form.
   * @param $destination_bundle
   *  The destination bundle.
   */
  public function copyDisplaySettings(FieldDefinitionInterface $field_definition, EntityDisplayBase $source_entity_display, $destination_bundle) {
    $field_name = $field_definition->getName();

    $component = $source_entity_display->getComponent($field_name);

    // The entity type ID of the displays being copied to and from.
    $display_entity_type_id = $source_entity_display->getEntityTypeId();

    // Load the corresponding display on the destination bundle.
    $destination_display = $this->entityTypeManager->getStorage($display_entity_type_id)->load($field_definition->getTargetEntityTypeId() . '.' . $destination_bundle . '.' . $source_entity_display->getMode());

    if (!empty($destination_display)) {
      if (is_null($component)) {
        $destination_display->removeComponent($field_name);
      }
      else {
        $destination_display->setComponent($field_name, $component);
      }

      // Save the display.
      $destination_display->save();
    }
    // TODO complain if the destination doens't exist.
  }

}
