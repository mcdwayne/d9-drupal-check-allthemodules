<?php

namespace Drupal\bundle_override_node\Plugin\bundle_override\EntityTypes\node;

use Drupal\bundle_override\Tools\BundleOverrideStorageTrait;
use Drupal\node\NodeStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\bundle_override_node\Plugin\bundle_override\EntityTypes\NodeBPluginManager;

/**
 * Class NodeBStorage.
 *
 * @package Drupal\bundle_override_node\Plugin\bundle_override\EntityTypes\node
 */
class NodeBStorage extends NodeStorage {

  use BundleOverrideStorageTrait;

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records, $load_from_revision = FALSE) {
    if (!$records) {
      return [];
    }

    $values = [];
    foreach ($records as $id => $record) {
      $values[$id] = [];
      // Skip the item delta and item value levels (if possible) but let the
      // field assign the value as suiting. This avoids unnecessary array
      // hierarchies and saves memory here.
      foreach ($record as $name => $value) {
        // Handle columns named [field_name]__[column_name] (e.g for field types
        // that store several properties).
        if ($field_name = strstr($name, '__', TRUE)) {
          $property_name = substr($name, strpos($name, '__') + 2);
          $values[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT][$property_name] = $value;
        }
        else {
          // Handle columns named directly after the field (e.g if the field
          // type only stores one property).
          $values[$id][$name][LanguageInterface::LANGCODE_DEFAULT] = $value;
        }
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
      $entities[$id] = NodeBPluginManager::me()
        ->getEntityByStorageData($entity_values, $this->entityTypeId, $bundle, array_keys($translations[$id]));
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    $parentNamespace = substr(__NAMESPACE__, 0, (strrpos(__NAMESPACE__, '\\')));
    return static::createFromPluginManagerClassName($parentNamespace . '\\NodeBPluginManager', $values);
  }

}
