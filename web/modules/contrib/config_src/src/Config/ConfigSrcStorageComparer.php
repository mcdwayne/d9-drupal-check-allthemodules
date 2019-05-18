<?php

namespace Drupal\config_src\Config;

use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;

/**
 * Defines a config storage comparer.
 */
class ConfigSrcStorageComparer extends StorageComparer {
  /**
   * {@inheritdoc}
   */
  public function createChangelist($element = array()) {
    if (count($element)) {
      $all_collection = array_keys($element);
    }
    else {
      $all_collection = $this->getAllCollectionNames();
      foreach ($all_collection as $item) {
        $element[$item] = array();
      }
    }

    foreach ($all_collection as $collection) {
      $this->changelist[$collection] = $this->getEmptyChangelist();
      $this->getAndSortConfigData($collection);
      // Only the listed ones should appear.
      $this->addChangelistCreate($collection, $element[$collection]);
      $this->addChangelistUpdate($collection, $element[$collection]);
      $this->addChangelistDelete($collection, $element[$collection]);

      // Only collections that support configuration entities can have renames.
      if ($collection == StorageInterface::DEFAULT_COLLECTION) {
        $this->addChangelistRename($collection);
      }
    }
    return $this;
  }

  /**
   * Creates the create changelist.
   *
   * The list of creates is sorted so that dependencies are created before
   * configuration entities that depend on them. For example, field storages
   * should be created before fields.
   *
   * @param string $collection
   *   The storage collection to operate on.
   *
   * @param array $configs
   *   Add selected configs.
   */
  protected function addChangelistCreate($collection, $configs = array()) {
    $creates = array_diff($this->sourceNames[$collection], $this->targetNames[$collection]);
    if (!empty($configs)) {
      foreach ($creates as $key => $name) {
        if (!in_array($name, $configs)) {
          unset($creates[$key]);
        }
      }
    }

    $this->addChangeList($collection, 'create', $creates);
  }

  /**
   * Creates the update changelist.
   *
   * The list of updates is sorted so that dependencies are created before
   * configuration entities that depend on them. For example, field storages
   * should be updated before fields.
   *
   * @param string $collection
   *   The storage collection to operate on.
   *
   * @param array $configs
   *   Add selected configs.
   */
  protected function addChangelistUpdate($collection, $configs = array()) {
    $recreates = array();
    foreach (array_intersect($this->sourceNames[$collection], $this->targetNames[$collection]) as $name) {
      $source_data = $this->getSourceStorage($collection)->read($name);
      $target_data = $this->getTargetStorage($collection)->read($name);
      if ($source_data !== $target_data) {
        if (in_array($name, $configs)) {
          if (isset($source_data['uuid']) && $source_data['uuid'] !== $target_data['uuid']) {
            // The entity has the same file as an existing entity but the UUIDs do
            // not match. This means that the entity has been recreated so config
            // synchronization should do the same.
            $recreates[] = $name;
          }
          else {
            $this->addChangeList($collection, 'update', array($name));
          }
        }
      }
    }

    if (!empty($recreates)) {
      // Recreates should become deletes and creates. Deletes should be ordered
      // so that dependencies are deleted first.
      $this->addChangeList($collection, 'create', $recreates, $this->sourceNames[$collection]);
      $this->addChangeList($collection, 'delete', $recreates, array_reverse($this->targetNames[$collection]));

    }
  }

  /**
   * Creates the delete changelist.
   *
   * The list of deletes is sorted so that dependencies are deleted after
   * configuration entities that depend on them. For example, fields should be
   * deleted after field storages.
   *
   * @param string $collection
   *   The storage collection to operate on.
   *
   * @param array $configs
   *   Add selected configs.
   */
  protected function addChangelistDelete($collection, $configs = array()) {
    $deletes = array_diff(array_reverse($this->targetNames[$collection]), $this->sourceNames[$collection]);
    if (!empty($configs)) {
      foreach ($deletes as $key => $name) {
        if (!in_array($name, $configs)) {
          unset($deletes[$key]);
        }
      }
    }

    $this->addChangeList($collection, 'delete', $deletes);
  }

}
