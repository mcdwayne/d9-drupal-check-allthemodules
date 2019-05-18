<?php
namespace Drupal\cloudinary_storage;

/**
 * Abstract for cloudinary storage.
 */
abstract class CloudinaryStorage {
  /**
   * A current file resource of Cloudinary.
   *
   * @var Array
   */
  protected $resource = NULL;

  /**
   * Object constructor.
   */
  public function __construct($resource = NULL, $loaded = TRUE) {
    if (is_null($resource)) {
      return;
    }

    if ($loaded && is_array($resource)) {
      $this->resource = $resource;
    }
    elseif (is_string($resource)) {
      $this->resource = $this->load($resource);
    }
    elseif (is_array($resource) && isset($resource['public_id'])) {
      $this->resource = $this->load($resource['public_id']);
    }
  }

  /**
   * Base implementation of getResource().
   */
  public function getResource() {
    return $this->resource;
  }

  /**
   * Insert or update resource.
   */
  abstract protected function save($resource);

  /**
   * Delete resource by public_id.
   */
  abstract protected function delete($public_id);

  /**
   * Delete folder resource by public_id.
   */
  abstract protected function deleteFolder($public_id);

  /**
   * Load resource by public_id.
   */
  abstract protected function load($public_id);

  /**
   * Preprae file or folder resource for folder update.
   */
  static protected function prepareFolderData($data, $update) {
    if (empty($data) || !is_array($data)) {
      $data = array();
    }

    // Append new file or folder if not exist.
    if (isset($update[CLOUDINARY_STORAGE_NEW]) && !in_array($update[CLOUDINARY_STORAGE_NEW], $data)) {
      $data[] = $update[CLOUDINARY_STORAGE_NEW];
    }

    // Remove old file or folder if exist.
    if (isset($update[CLOUDINARY_STORAGE_REMOVE]) && in_array($update[CLOUDINARY_STORAGE_REMOVE], $data)) {
      $data = array_flip($data);
      unset($update[CLOUDINARY_STORAGE_REMOVE]);
      $data = array_flip($data);
    }

    return $data;
  }

  /**
   * Clear all resources data from storage.
   */
  public function clear() {
    $this->deleteFolder('');
  }

  /**
   * Update child files or folders in folder resource.
   */
  public function folderUpdate($path, $file = array(), $folder = array()) {
    if ($path === FALSE) {
      return;
    }

    $resource = $this->load($path);

    // Return if resource doesn't exist in storage.
    if (!$resource) {
      return;
    }

    // Prepare sub files of folder.
    if ($file) {
      $resource['files'] = self::prepareFolderData($resource['files'], $file);
    }

    // Prepare sub folders of folder.
    if ($folder) {
      $resource['folders'] = self::prepareFolderData($resource['folders'], $folder);
    }

    // Save changes back into folder resource.
    $this->save($resource);
  }

  /**
   * Remove old resource or save new resource.
   *
   * Return array with path and filename.
   */
  public function resourceUpdate($new = TRUE, $resource = NULL) {
    if (!$resource) {
      $resource = $this->resource;
    }

    $path = FALSE;
    $file = '';

    if (isset($resource['public_id'])) {
      $file = $resource['public_id'];
      $path = dirname($file);

      // Append file format into filename.
      if (!empty($resource['format'])) {
        $file .= '.' . $resource['format'];
      }

      $file = pathinfo($file, PATHINFO_BASENAME);

      if ($new) {
        $this->save($resource);
      }
      else {
        if ($resource['mode'] == CLOUDINARY_STREAM_WRAPPER_FOLDER) {
          $this->deleteFolder($resource['public_id']);
        }
        elseif ($resource['mode'] == CLOUDINARY_STREAM_WRAPPER_FILE) {
          $this->delete($resource['public_id']);
        }
      }
    }

    return array($path, $file);
  }

}
