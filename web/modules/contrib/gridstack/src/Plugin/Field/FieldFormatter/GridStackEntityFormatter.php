<?php

namespace Drupal\gridstack\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'GridStack Entity' formatter.
 *
 * @FieldFormatter(
 *   id = "gridstack_entity",
 *   label = @Translation("GridStack Entity"),
 *   description = @Translation("Display the entity reference as a GridStack."),
 *   field_types = {"entity_reference"},
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class GridStackEntityFormatter extends GridStackEntityFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();

    return $storage->isMultiple() && $storage->getSetting('target_type') !== 'media';
  }

}
