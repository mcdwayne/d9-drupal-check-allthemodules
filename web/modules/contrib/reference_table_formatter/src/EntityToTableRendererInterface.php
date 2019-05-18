<?php

namespace Drupal\reference_table_formatter;

/**
 * An interface for an renderer which spits out tables from entities.
 */
interface EntityToTableRendererInterface {

  /**
   * Render the entities to a table.
   *
   * @param string $entity_type
   *   The entity type of the given entities.
   * @param string $bundle
   *   The bundle that the entities are composed of.
   * @param array $entities
   *   An array of entities to put into the table.
   * @param array $settings
   *   The settings array from the field formatter base containing keys:
   *     - view_mode: The target view mode to render the field settings from.
   *     - show_entity_label: If we should display the entity label.
   *     - empty_cell_value: The value to show in empty cells.
   *
   * @return array
   *   A table renderable array.
   */
  public function getTable($entity_type, $bundle, $entities, $settings);

}
