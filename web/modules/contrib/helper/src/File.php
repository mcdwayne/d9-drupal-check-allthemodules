<?php

namespace Drupal\helper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;

class File {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $fileStorage;

  /**
   * File constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->fileStorage = $entity_type_manager->getStorage('file');
  }

  /**
   * Creates or reuses a file object based on a URI.
   *
   * @param string $uri
   *   A string containing the URI.
   * @param bool $reuse_existing
   *   If TRUE will try to reuse an existing file with the same URI.
   *   If FALSE will always create a new file.
   *
   * @return \Drupal\file\FileInterface
   *   A file object.
   */
  public function createOrReuseFromUri($uri, $reuse_existing = TRUE) {
    if ($reuse_existing) {
      // Check if this file already exists, and if so, return that.
      $files = $this->fileStorage->loadByProperties(['uri' => $uri]);
      if ($valid_files = array_filter($files, [$this, 'filterValidFiles'])) {
        return reset($valid_files);
      }
    }

    // If an existing file could not be found, create a new file.
    return $this->fileStorage->create([
      'uri' => $uri,
      'uid' => \Drupal::currentUser()->id(),
    ]);
  }

  /**
   * Filter callback; Permanent files or temporary files owned by current user.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file object.
   *
   * @return bool
   *   TRUE if the file is valid, or FALSE otherwise.
   */
  public static function filterValidFiles(FileInterface $file) {
    return $file->isPermanent() || $file->getOwnerId() == \Drupal::currentUser()->id();
  }

}
