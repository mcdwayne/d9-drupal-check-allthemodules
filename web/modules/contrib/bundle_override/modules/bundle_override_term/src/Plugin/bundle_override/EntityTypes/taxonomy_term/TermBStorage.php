<?php

namespace Drupal\bundle_override_term\Plugin\bundle_override\EntityTypes\taxonomy_term;

use Drupal\bundle_override\Tools\BundleOverrideStorageTrait;
use Drupal\Core\Language\LanguageInterface;
use Drupal\bundle_override_term\Plugin\bundle_override\EntityTypes\TermBPluginManager;
use Drupal\taxonomy\TermStorage;

/**
 * Class TermBStorage.
 *
 * @package Drupal\bundle_override_term\Plugin\bundle_override\EntityTypes\taxonomy_term
 */
class TermBStorage extends TermStorage {

  use BundleOverrideStorageTrait;

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records, $load_from_revision = FALSE) {
    if (!$records) {
      return [];
    }

    // Get the names of the fields that are stored in the base table and, if
    // applicable, the revision table. Other entity data will be loaded in
    // loadFromSharedTables() and loadFromDedicatedTables().
    $field_names = $this->tableMapping->getFieldNames($this->baseTable);
    if ($this->revisionTable) {
      $field_names = array_unique(array_merge($field_names, $this->tableMapping->getFieldNames($this->revisionTable)));
    }

    $values = [];
    foreach ($records as $id => $record) {
      $values[$id] = [];
      // Skip the item delta and item value levels (if possible) but let the
      // field assign the value as suiting. This avoids unnecessary array
      // hierarchies and saves memory here.
      foreach ($field_names as $field_name) {
        $field_columns = $this->tableMapping->getColumnNames($field_name);
        // Handle field types that store several properties.
        if (count($field_columns) > 1) {
          foreach ($field_columns as $property_name => $column_name) {
            if (property_exists($record, $column_name)) {
              $values[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT][$property_name] = $record->{$column_name};
              unset($record->{$column_name});
            }
          }
        }
        // Handle field types that store only one property.
        else {
          $column_name = reset($field_columns);
          if (property_exists($record, $column_name)) {
            $values[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT] = $record->{$column_name};
            unset($record->{$column_name});
          }
        }
      }

      // Handle additional record entries that are not provided by an entity
      // field, such as 'isDefaultRevision'.
      foreach ($record as $name => $value) {
        $values[$id][$name][LanguageInterface::LANGCODE_DEFAULT] = $value;
      }
    }

    // Initialize translations array.
    $translations = array_fill_keys(array_keys($values), []);

    // Load values from shared and dedicated tables.
    $this->loadFromSharedTables($values, $translations, $load_from_revision);
    $this->loadFromDedicatedTables($values, $load_from_revision);

    $entities = [];
    foreach ($values as $id => $entity_values) {
      $bundle = $this->bundleKey ? $entity_values[$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] : FALSE;
      // Turn the record into an entity class.
      $entities[$id] = TermBPluginManager::me()
        ->getEntityByStorageData($entity_values, $this->entityTypeId, $bundle, array_keys($translations[$id]));
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    $parentNamespace = substr(__NAMESPACE__, 0, (strrpos(__NAMESPACE__, '\\')));
    return static::createFromPluginManagerClassName($parentNamespace . '\\TermBPluginManager', $values);
  }

}
