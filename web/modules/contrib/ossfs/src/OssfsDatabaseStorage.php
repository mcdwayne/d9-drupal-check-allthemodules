<?php

namespace Drupal\ossfs;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Defines the Database storage.
 *
 * @see \Drupal\Core\Config\DatabaseStorage
 */
class OssfsDatabaseStorage implements OssfsStorageInterface {

  use DependencySerializationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs an OssfsDatabaseStorage object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing file metadata.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($uri) {
    return (bool) $this->connection->queryRange('SELECT 1 FROM {ossfs_file} WHERE uri = :uri', 0, 1, [
      ':uri' => $uri,
    ])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function read($uri) {
    return $this->connection->select('ossfs_file', 'of')
      ->fields('of')
      ->condition('uri', $uri)
      ->execute()
      ->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $uris) {
    return $this->connection->select('ossfs_file', 'of')
      ->fields('of')
      ->condition('uri', $uris, 'IN')
      ->execute()
      ->fetchAllAssoc('uri', \PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function write($uri, array $data) {
    $options = ['return' => Database::RETURN_AFFECTED];
    return (bool) $this->connection->merge('ossfs_file', $options)
      ->key('uri', $uri)
      ->fields($data)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($uri) {
    $options = ['return' => Database::RETURN_AFFECTED];
    return (bool) $this->connection->delete('ossfs_file', $options)
      ->condition('uri', $uri)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function rename($uri, $new_uri) {
    try {
      return $this->doRename($uri, $new_uri);
    }
    catch (IntegrityConstraintViolationException $e) {
      // If there was an exception: 'UNIQUE constraint failed: ossfs_file.uri',
      // try to delete the existent one.
      if ($this->delete($new_uri)) {
        return $this->doRename($uri, $new_uri);
      }
      // Some other failure that we can not recover from.
      throw $e;
    }
  }

  /**
   * Helper method so we can re-try a rename.
   *
   * @return bool
   */
  protected function doRename($uri, $new_uri) {
    $options = ['return' => Database::RETURN_AFFECTED];
    return (bool) $this->connection->update('ossfs_file', $options)
      ->fields(['uri' => $new_uri])
      ->condition('uri', $uri)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix) {
    if ($prefix !== '' && substr($prefix, -1) !== '/') {
      throw new \InvalidArgumentException('Path prefix must be an empty string or a string with a trailing slash');
    }

    $query = $this->connection->select('ossfs_file', 'of');
    $query->fields('of', ['uri']);
    if ($prefix !== '') {
      $query->condition('uri', $this->connection->escapeLike($prefix) . '%', 'LIKE');
      $query->condition('uri', $this->connection->escapeLike($prefix) . '%/%', 'NOT LIKE');
    }
    return $query->execute()->fetchCol();
  }

}
