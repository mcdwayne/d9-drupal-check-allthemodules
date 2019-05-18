<?php
namespace Drupal\cloudinary_storage_db;

use Drupal\cloudinary_storage\CloudinaryStorage;

/**
 * @file
 * Database storage implementation for uploaded Cloudinary files.
 */

/**
 * Implements cloudinary storage with database.
 */
class CloudinaryStorageDb extends CloudinaryStorage {
  /**
   * Create or update cloudinary file resource into db.
   */
  protected function save($resource = array()) {
    if (isset($resource['public_id']) && !empty($resource['mode'])) {
      $data = array(
        'public_id' => $resource['public_id'],
        'mode' => $resource['mode'],
        'metadata' => serialize($resource),
      );

      db_merge('cloudinary_storage')
        ->key(array('public_id' => $data['public_id']))
        ->fields($data)
        ->execute();
    }
  }

  /**
   * Delete cloudinary file resource from db.
   */
  protected function delete($public_id) {
    db_delete('cloudinary_storage')
      ->condition('public_id', $public_id)
      ->execute();
  }

  /**
   * Delete cloudinary folder resource from db.
   */
  protected function deleteFolder($public_id) {
    // Only remove file and folder resource in this folder.
    // Cloudinary can not delete folder, parent folder update is not necessary.
    db_delete('cloudinary_storage')
      ->condition('public_id', db_like($public_id) . '%', 'LIKE')
      ->execute();
  }

  /**
   * Load cloudinary file resource from db.
   */
  protected function load($public_id) {
    $resource = array();

    $result = db_select('cloudinary_storage', 'cs')
      ->fields('cs')
      ->condition('public_id', $public_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if ($result && !empty($result->metadata)) {
      $resource = (array) unserialize($result->metadata);
    }

    return $resource;
  }

  /**
   * Clear all resources data from db.
   */
  public function clear() {
    db_query('TRUNCATE {cloudinary_storage}');
  }

}
