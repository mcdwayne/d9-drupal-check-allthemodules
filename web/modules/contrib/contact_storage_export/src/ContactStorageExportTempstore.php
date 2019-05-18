<?php

namespace Drupal\contact_storage_export;

/**
 * Class ContactStorageExportTempstore.
 *
 * @package Drupal\contact_storage_export
 */
class ContactStorageExportTempstore {

  /**
   * Save the data to the tempstore.
   *
   * @param int $fid
   *   The file id.
   * @param string $filename
   *   The filename.
   */
  public static function setTempstore($fid, $filename) {
    $tempstore = \Drupal::service('user.private_tempstore')
      ->get('contact_storage_export');

    // Get existing data.
    $data = $tempstore->get('data');

    // Possibly have more than one export running at a time, set unique key.
    $data = [];
    $key = 0;
    if (is_array($data)) {
      $data = self::cleanTempstoreData($data);
      if ($keys = array_keys($data)) {
        $key = (max($keys) + 1);
      }
    }

    // Set data.
    $data[$key] = [
      'created' => time(),
      'fid' => $fid,
      'filename' => $filename,
    ];

    // Save tempstore.
    $tempstore->set('data', $data);

    return $key;
  }

  /**
   * Prevent overload of data in tempstore, clean up older than 60 min.
   *
   * @param array $data
   *   The current tempstore data.
   *
   * @return array
   *   The cleaned up tempstore data.
   */
  protected static function cleanTempstoreData(array $data) {
    $delete_if_older_than = strtotime('-60 minutes');
    foreach ($data as $key => $value) {
      if ($value['created'] < $delete_if_older_than) {
        unset($data[$key]);
      }
    }
    return $data;
  }

}
