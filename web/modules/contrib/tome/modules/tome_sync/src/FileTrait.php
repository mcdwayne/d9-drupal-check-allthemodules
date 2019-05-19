<?php

namespace Drupal\tome_sync;

use Drupal\Core\Config\StorageException;
use Drupal\Core\Site\Settings;

/**
 * Contains shared functionality for dealing with files.
 *
 * @internal
 */
trait FileTrait {

  /**
   * Gets the file directory.
   *
   * @return string
   *   The file directory.
   */
  protected function getFileDirectory() {
    return Settings::get('tome_files_directory', '../files') . '/public';
  }

  /**
   * Ensures that the file directory exists.
   */
  protected function ensureFileDirectory() {
    $file_directory = $this->getFileDirectory();
    file_prepare_directory($file_directory, FILE_CREATE_DIRECTORY);
    file_save_htaccess($file_directory);
    if (!file_exists($file_directory)) {
      throw new StorageException('Failed to create config directory ' . $file_directory);
    }
  }

}
