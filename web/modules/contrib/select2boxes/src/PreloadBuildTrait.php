<?php

namespace Drupal\select2boxes;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Trait PreloadBuildTrait.
 *
 * @package Drupal\select2boxes
 */
trait PreloadBuildTrait {

  /**
   * Build preloaded entries list.
   *
   * @param string $count
   *   Number of entries will be preloaded, or empty string to load all.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field definition.
   *
   * @return array
   *   Preloaded entries list array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildPreLoaded($count, FieldDefinitionInterface $fieldDefinition) {
    $entities = [];
    // Return empty array if the count is less than 1.
    if ($count <= 0 && $count != '') {
      return $entities;
    }

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $selection */
    $selection = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($fieldDefinition);
    $referencable = $selection->getReferenceableEntities(NULL, 'CONTAINS', $count);

    foreach ($referencable as $value) {
      $entities += $value;
    }
    return $entities;
  }

}
