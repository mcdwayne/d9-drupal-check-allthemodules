<?php

namespace Drupal\drupal_reset;
use Drupal\Core\File\FileSystemInterface;

/**
 * Class DeleteFiles.
 *
 * @package Drupal\drupal_reset
 */
class DeleteFiles implements DeleteFilesInterface{

  protected $fileSystem;

  /**
   * Constructor.
   */
  public function __construct(FileSystemInterface $fileSystemInterface) {
    $this->fileSystem = $fileSystemInterface;
  }

  /**
   * Delete all public and private files.
   */
  public function deletefiles() {
    $this->rrmdir($this->fileSystem->realpath('public://'), FALSE);
    $this->rrmdir($this->fileSystem->realpath('private://'), FALSE);
  }

  /**
   * Recursive directory deletion.
   *
   * @param string $dir
   *   The directory to delete.
   * @param bool $rmdir
   *   If TRUE, delete the directory. Otherwise, delete the contents, but not the
   *   directory itself.
   */
  public function rrmdir($dir, $rmdir = TRUE) {
    if (!empty($dir) && is_dir($dir)) {
      foreach (scandir($dir) as $object) {
        if ($object !== '.' && $object !== '..') {
          $this_object = $dir . DIRECTORY_SEPARATOR . $object;
          if (filetype($this_object) === 'dir') {
            $this->rrmdir($this_object);
          }
          else {
            unlink($this_object);
          }
        }
      }
      if ($rmdir) {
        rmdir($dir);
      }
    }
  }

}
