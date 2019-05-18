<?php

namespace Drupal\content_synchronizer\Base;

/**
 * Json writer tool.
 */
trait JsonWriterTrait {

  /**
   * Save json in the destination file.
   */
  protected function writeJson($data, $destination) {

    // Create dir :
    $dir = explode('/', $destination);
    array_pop($dir);
    $dir = implode('/', $dir);
    $this->createDirectory($dir);

    file_save_data(json_encode($data), $destination, FILE_EXISTS_REPLACE);
  }

  /**
   * Get json decode data from a file.
   */
  protected function getDataFromFile($path) {
    if (file_exists($path)) {
      return json_decode(file_get_contents($path), TRUE);
    }
    return NULL;
  }

  /**
   * Create a directory if not exists.
   */
  protected function createDirectory($dir) {
    if (!is_dir($dir)) {
      file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
    }
  }

  /**
   * Create a directory tree.
   */
  protected function createDirTreeForFileDest($destination, $root = '/') {
    $destinationItems = explode('/', $destination);
    $fileName = array_pop($destinationItems);

    // Create destination tree.
    foreach ($destinationItems as $dirItem) {
      $root .= '/' . $dirItem;
      $this->createDirectory($root);
    }

    return $root . '/' . $fileName;
  }

}
