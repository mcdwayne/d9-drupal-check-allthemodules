<?php

namespace Drupal\filebrowser\Services;

use Drupal\Core\Database\Connection;

class FilebrowserStorage {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a BookOutlineStorage object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function storageLoadMultipleData($nids, $access = true) {
    $query = $this->connection->select('filebrowser_nodes', 'fb', ['fetch' => \PDO::FETCH_ASSOC]);
    $query->fields('fb');
    $query->condition('fb.nid', $nids, 'IN');

    if ($access) {
      $query->addTag('node_access');
      $query->addMetaData('base_table', 'filebrowser');
    }
    return $query->execute();
  }

  public function storageLoadData($nid) {
    $query = $this->connection->select('filebrowser_nodes', 'f', ['fetch' => \PDO::FETCH_ASSOC]);
    $query->fields('f');
    $query->condition('f.nid', $nid, '=');
    return $query->execute()->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function getFilebrowsers() {
    return $this->connection->query("SELECT DISTINCT(fid) FROM {filebrowser_nodes}")->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function hasFilebrowsers() {
    return (bool) $this->connection
      ->query('SELECT count(fid) FROM {filebrowsers}')
      ->fetchField();
  }


  /**
   * Delete entry's of filebrowser table
   *
   * @param int $nid Id of node being deleted.
   * @return int
   */
  public function deleteNode($nid) {
    return $this->connection->delete('filebrowser_nodes')
      ->condition('nid', $nid)
      ->execute();
  }

  public function deleteContent($nid) {
    return $this->connection->delete('filebrowser_content')
      ->condition('nid', $nid)
      ->execute();
  }

  public function genericDeleteMultiple($table, $col_name, $values) {
    $query = "DELETE FROM {" . $table .  "} WHERE " . $col_name . " IN (" .
      $values . ")";
    return $this->connection->query($query);
  }

  /**
   * {@inheritdoc}
   */
  public function insert($data) {
    return $this->connection
      ->insert('filebrowser_nodes')
      ->fields([
          'nid' => $data['nid'],
          'folder_path' => $data['folder_path'],
          'properties' => $data['properties'],
        ]
      )
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function update($data) {
    return $this->connection
      ->update('filebrowser_nodes')
      ->fields($data)
      ->condition('nid', $data['nid'], '=')
      ->execute();
  }

  /**
   * @param int $nid
   * @param string $path
   * @return mixed
   */
  public function loadRecordFromPath($nid, $path) {
    //var_dump($nid, $path);die;
    return $this->connection->query("SELECT * FROM {filebrowser_content}
      WHERE nid = :nid AND path = :path AND root = :root",
        [':nid' => $nid, ':path' => $path, ':root' => $path])->fetchAssoc();
  }

  public function deleteFileRecords($ids) {
    return
      $this->connection
       ->delete('filebrowser_content')
       ->condition('fid', $ids, 'IN')
       ->execute();
  }

  // in use
  public function loadRecordsFromRoot($nid, $root) {
    return $this->connection->query('SELECT * FROM {filebrowser_content} where nid = :nid and root = :root', [
      ':nid' => $nid,
      ':root' => $root])->fetchAllAssoc('path', \PDO::FETCH_ASSOC);
  }

  public function loadAllRecordsFromRoot($nid) {
    return $this->connection->query('SELECT fid, path, root, file_data FROM {filebrowser_content} where nid = :nid', [
      ':nid' => $nid])->fetchAllAssoc('fid');
  }

  /**
   * @param string $key name of the key column
   * @param mixed $key_value
   * @param string $field_name Field who's value to change
   * @param mixed $value New value
   * @return \Drupal\Core\Database\StatementInterface|int|null
   */
  public function updateContentField($key, $key_value, $field_name, $value) {
    return
      $this->connection->update('filebrowser_content')
        ->fields([$field_name => $value])
        ->condition($key, $key_value, '=')
        ->execute();
  }

  public function updateRootContentField($nid, $old_root, $new_root) {
    return
      $this->connection->update('filebrowser_content')
        ->fields(['root' => $new_root])
        ->condition('nid', $nid, '=')
        ->condition('root', $old_root, '=')
        ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function insertRecord($record) {
    //fixme: root folder has no 'file_data'
    if(!isset($record['nid'])) {
      debug($record);
    }
    // todo: check file_data index is not set for an empty filebrowser node.
    if (empty($record['file_data'])) {
      $record['file_data'] = '';
    }

    return $this->connection
      ->insert('filebrowser_content')
      ->fields([
        'nid' => $record['nid'],
        'root' => $record['root'],
        'path' => $record['path'],
        'file_data' => serialize($record['file_data']),
      ])
      ->execute();
  }

  public function loadRecord($fid) {
    return $this->connection->query ('SELECT * FROM {filebrowser_content} WHERE fid= :fid', [':fid' => $fid])->fetchAssoc();
  }

  public function loadNodeRecord($nid) {
    return $this->connection->query('SELECT * FROM {filebrowser_nodes} WHERE nid = :nid', [':nid' => $nid])->fetchAssoc();
  }

  /**
   * @param array $data   [$fid] => $description
   * @return \Drupal\Core\Database\StatementInterface|int|null
   */
    public function storeDescriptionMultiple($data) {
      //var_dump($data);
      $query = "UPDATE {filebrowser_content} SET description = :description WHERE fid = :fid";
      foreach ($data as $row) {
        print_r($row);
        echo("<br>");
        $this->connection->query($query, [':description' => $row['description'], ':fid' => $row['fid']]);
      }
    }

  /**
   * @param array $fids
   * @return mixed
   */
  public function nodeContentLoadMultiple($fids) {
    $query = $this->connection->select('filebrowser_content', 'f', ['fetch' => \PDO::FETCH_ASSOC]);
    $query->fields('f');
    $query->condition('f.fid', $fids, 'IN');
    return $query->execute()->fetchAllAssoc('fid');
  }

}