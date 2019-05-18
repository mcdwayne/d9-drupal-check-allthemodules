<?php

namespace Drupal\nimbus;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Connection;

/**
 * Class UuidUpdater.
 *
 * @package Drupal\nimbus
 */
class UuidUpdater implements UuidUpdaterInterface {

  /**
   * The current active fileStorage class.
   *
   * @var \Drupal\Core\Config\FileStorage
   */
  private $sourceStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * UuidUpdater constructor.
   *
   * @param \Drupal\Core\Config\FileStorage $source_storage
   *   The source storage for file check.
   * @param \Drupal\Core\Database\Connection $database
   *   The database for database check.
   */
  public function __construct(FileStorage $source_storage, Connection $database) {
    $this->sourceStorage = $source_storage;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $result = $this->getEntries();
    $result = $this->filterEntries($result);
    foreach ($result as $element) {
      $current_database_value = $element->getActiveConfig();
      $current_database_value['uuid'] = $element->getStagingUuid();
      $this->updateEntry($element->name, $current_database_value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function filterEntries(array $entries) {
    $response = [];
    foreach ($entries as $name => $entry) {
      if ($entry->isUuidNotEquivalent()) {
        $response[$name] = $entry;
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntries() {
    $response = [];
    $result = $this->loadConfigEntries();
    foreach ($result as $element) {
      if ($this->sourceStorage->exists($element->name)) {
        $value = $this->sourceStorage->read($element->name);
        $database_value = unserialize($element->data);
        $response[$element->name] = new ConfigChange($database_value, $value);
      }
    }
    return $response;
  }

  /**
   * Load all config entries.
   *
   * @return mixed
   *   Return from database
   */
  private function loadConfigEntries() {
    $query = $this->database->select('config', 'cf');
    $query->condition('collection', '');
    $query->addField('cf', 'name');
    $query->addField('cf', 'data');
    $result = $query->execute()->fetchAll();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntry($config_name, array $new_data, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $new_database_entry = serialize($new_data);
    $this->database
      ->update('config')
      ->condition('collection', $collection)
      ->condition('name', $config_name)
      ->fields(
        [
          'data' => $new_database_entry,
        ]
      )->execute();
  }

}
