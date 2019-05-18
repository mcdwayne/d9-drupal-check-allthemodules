<?php

namespace Drupal\migrate_process_extras\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Look-up any entity.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_lookup"
 * )
 */
class EntityLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$value) {
      return NULL;
    }
    return $this->lookupEntity($this->configuration['entity_type_id'], $this->configuration['bundle'] ?? FALSE, $this->configuration['field_name'], $value, !empty($this->configuration['allow_multiple']));
  }

  /**
   * Lookup the entity.
   *
   * @param string $entity_type_id
   *   The entity type Id.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name.
   * @param string $value
   *   The value of the field.
   * @param bool $allow_multiple
   *   (optional) Allow multiple entities to be returned.
   *
   * @return int|false
   *   The Id or false if the entity was not found.
   */
  protected function lookupEntity($entity_type_id, $bundle, $field_name, $value, $allow_multiple = FALSE) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $query = \Drupal::entityQuery($entity_type_id)
      ->condition($field_name, $value);

    if ($bundle && $entity_type->hasKey('bundle')) {
      $query->condition($entity_type->getKey('bundle'), $bundle);
    }

    $results = $query->execute();

    if (!$results) {
      return NULL;
    }

    if (count($results) > 1 && !$allow_multiple) {
      drupal_set_message(sprintf('Invalid number of results: %s for %s on %s with %s = %s', count($results), $bundle, $entity_type_id, $field_name, $value));
      return NULL;
    }

    return $allow_multiple ? array_values($results) : reset($results);
  }

}
