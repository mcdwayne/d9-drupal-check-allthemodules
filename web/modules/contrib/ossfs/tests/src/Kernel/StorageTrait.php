<?php

namespace Drupal\Tests\ossfs\Kernel;

use OSS\Core\MimeTypes;

trait StorageTrait {

  protected function insertRecord($uri, $type = 'file', $imagesize = '') {
    // Write the file metadata to the local storage.
    $insert = $this->connection->insert('ossfs_file')
      ->fields(['uri', 'type', 'filemime', 'filesize', 'imagesize', 'changed']);
    $data = [
      'uri' => $uri,
      'type' => $type,
      'filemime' => $type === 'file' ? (MimeTypes::getMimetype($uri) ?: 'application/octet-stream') : '',
      'filesize' => $type === 'file' ? 100 : 0,
      'imagesize' => $imagesize,
      'changed' => REQUEST_TIME,
    ];
    $insert->values($data);
    $insert->execute();
  }

  protected function selectAllRecords() {
    return $this->connection->select('ossfs_file', 'of')
      ->fields('of')
      ->execute()
      ->fetchAllAssoc('uri', \PDO::FETCH_ASSOC);
  }

}
