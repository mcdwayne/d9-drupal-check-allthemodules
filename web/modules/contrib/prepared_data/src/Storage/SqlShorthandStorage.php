<?php

namespace Drupal\prepared_data\Storage;

use Drupal\prepared_data\Shorthand\Shorthand;
use Drupal\prepared_data\Shorthand\ShorthandInterface;

/**
 * SQL implementation for the shorthand storage of prepared data keys.
 */
class SqlShorthandStorage implements ShorthandStorageInterface {

  use SqlStorageTrait;

  /**
   * A list of cached shorthand instances.
   *
   * @var \Drupal\prepared_data\Shorthand\ShorthandInterface[]
   */
  protected $instances;

  /**
   * Maps data query keys to shorthand instances.
   *
   * @var \Drupal\prepared_data\Shorthand\ShorthandInterface[]
   */
  protected $queryMap;

  /**
   * Keeps in mind how many times shorthands have been loaded.
   *
   * This is used to reset in-memory caching to prevent memory exceedance.
   *
   * @var int
   */
  protected $loadCount = 0;

  /**
   * The table which stores shorthand records.
   *
   * @var string
   */
  protected static $table = 'prepared_data_short';

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    if (!isset($this->instances[$id])) {
      $this->loadCount();
      try {
        $instance = $this->doRead($id);
      }
      catch (\Exception $e) {
        // If there was an exception, try to create the table.
        if ($this->ensureTableExists()) {
          $instance = $this->doRead($id);
        }
        else {
          // Some other failure that we can not recover from.
          throw $e;
        }
      }
      if (!empty($instance)) {
        $this->queryMap[$instance->getDataQuery()] = $instance;
        $this->instances[$id] = $instance;
      }
      else {
        $this->instances[$id] = FALSE;
      }
    }
    return $this->instances[$id] ? $this->instances[$id] : NULL;
  }

  /**
   * Reads the shorthand record from the database by given id.
   *
   * @param string $id
   *   The shorthand ID.
   *
   * @return \Drupal\prepared_data\Shorthand\Shorthand|null
   *   The shorthand instance if found, NULL otherwise.
   */
  protected function doRead($id) {
    $db = $this->getDatabase();
    $query = $db->select(static::$table, 'sh')->fields('sh', ['id', 'q']);
    $query->range(0, 1);
    $query->where('sh.id = :id', [':id' => $id]);
    $row = $query->execute()->fetchAssoc();
    if (!empty($row)) {
      $data_query = [];
      if (!empty($row['q'])) {
        parse_str($row['q'], $data_query);
      }
      $subset_keys = !empty($data_query['sk']) ? $data_query['sk'] : [];
      return new Shorthand($row['id'], $data_query['k'], $subset_keys);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadFor($key, $subset_keys = []) {
    $data_query = Shorthand::buildDataQuery($key, $subset_keys);
    if (!isset($this->queryMap[$data_query])) {
      $this->loadCount();
      try {
        $instance = $this->doReadFor($key, $data_query);
      }
      catch (\Exception $e) {
        // If there was an exception, try to create the table.
        if ($this->ensureTableExists()) {
          $instance = $this->doReadFor($key, $data_query);
        }
        else {
          // Some other failure that we can not recover from.
          throw $e;
        }
      }
      if (!empty($instance)) {
        $this->queryMap[$data_query] = $instance;
        $this->instances[$instance->id()] = $instance;
      }
      else {
        $this->queryMap[$data_query] = FALSE;
      }
      return $instance;
    }
    else {
      $instance = $this->queryMap[$data_query];
      return $instance ? $instance : NULL;
    }
  }

  /**
   * Reads the shorthand from the database for given key and subset keys.
   *
   * @param string $key
   *   The data key.
   * @param string $data_query
   *   The data query.
   *
   * @return \Drupal\prepared_data\Shorthand\Shorthand|null
   *   The shorthand instance if found, NULL otherwise.
   */
  protected function doReadFor($key, $data_query) {
    $db = $this->getDatabase();
    $query = $db->select(static::$table, 'sh')->fields('sh', ['id', 'q']);
    $query->range(0, 1);
    // Index is on k column, thus add it as
    // condition to prevent a full-table scan.
    $query->where('sh.k = :k', [':k' => $key]);
    $query->where('sh.q = :q', [':q' => $data_query]);
    $row = $query->execute()->fetchAssoc();
    if (!empty($row)) {
      $data_query = [];
      if (!empty($row['q'])) {
        parse_str($row['q'], $data_query);
      }
      $subset_keys = !empty($data_query['sk']) ? $data_query['sk'] : [];
      return new Shorthand($row['id'], $data_query['k'], $subset_keys);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function save(ShorthandInterface $shorthand) {
    $id = $shorthand->id();
    if (!isset($this->instances[$id])) {
      $this->loadCount();
    }
    $this->instances[$id] = $shorthand;
    $this->queryMap[$shorthand->getDataQuery()] = $shorthand;
    try {
      return $this->doWrite($shorthand);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if ($this->ensureTableExists()) {
        return $this->doWrite($shorthand);
      }
      // Some other failure that we can not recover from.
      throw $e;
    }
  }

  /**
   * Writes the shorthand into the database table.
   *
   * @param \Drupal\prepared_data\Shorthand\ShorthandInterface
   *   The shorthand instance to write.
   */
  protected function doWrite(ShorthandInterface $shorthand) {
    $db = $this->getDatabase();
    $db->upsert(static::$table)
      ->key('id')
      ->fields(['id', 'k', 'q'], [$shorthand->id(), $shorthand->key(), $shorthand->getDataQuery()])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    if (!empty($this->instances[$id])) {
      $instance = $this->instances[$id];
      unset($this->instances[$id]);
      unset($this->queryMap[$instance->getDataQuery()]);
    }
    $db = $this->getDatabase();
    $delete = $db->delete(static::$table)->where('id = :id', [':id' => $id]);
    try {
      return $delete->execute();
    }
    catch (\Exception $e) {
      if ($this->ensureTableExists()) {
        return $delete->execute();
      }
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFor($key, $subset_keys = []) {
    if (!isset($key)) {
      throw new SqlStorageException('Key must be set for deletion.');
    }
    $data_query = Shorthand::buildDataQuery($key, $subset_keys);
    if (!empty($this->queryMap[$data_query])) {
      $instance = $this->queryMap[$data_query];
      unset($this->instances[$instance->id()]);
      unset($this->queryMap[$data_query]);
    }
    $db = $this->getDatabase();
    $delete = $db->delete(static::$table);
    $delete->where('k = :k', [':k' => $key]);
    if (!empty($subset_keys)) {
      $delete->where('q = :q', [':q' => $data_query]);
    }
    try {
      return $delete->execute();
    }
    catch (\Exception $e) {
      if ($this->ensureTableExists()) {
        return $delete->execute();
      }
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    $this->instances = [];
    $this->queryMap = [];
    $this->loadCount = 0;
  }

  /**
   * Returns the schema definition for the shorthand table.
   *
   * @return array
   */
  static protected function schemaDefinition() {
    $schema = [
      'description' => 'The table for storing shorthands of prepared data keys.',
      'fields' => [
        'id' => [
          'description' => 'The shorthand ID.',
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
        ],
        'k' => [
          'description' => 'The represented prepared data key.',
          'type' => 'varchar',
          'length' => 187,
          'not null' => TRUE,
        ],
        'q' => [
          'description' => 'The whole data query including key and subset keys.',
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'normal',
        ],
      ],
      'indexes' => [
        'key' => ['k'],
      ],
      'primary key' => ['id'],
    ];
    return $schema;
  }

  /**
   * Counts loading and clears cache when count limit has been exceeded.
   */
  protected function loadCount() {
    $this->loadCount++;
    if ($this->loadCount > 100) {
      // Clear in-memory caching to prevent memory exceedance.
      $this->clearCache();
    }
  }

}
