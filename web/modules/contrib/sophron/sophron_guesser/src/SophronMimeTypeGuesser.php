<?php

namespace Drupal\sophron_guesser;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\sophron\MimeMapManager;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Makes possible to guess the MIME type of a file using its extension.
 */
class SophronMimeTypeGuesser implements MimeTypeGuesserInterface {

  /**
   * The MIME map manager service.
   *
   * @var \Drupal\sophron\MimeMapManager
   */
  protected $mimeMapManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a SophronMimeTypeGuesser object.
   *
   * @param \Drupal\sophron\MimeMapManager $mime_map_manager
   *   The MIME map manager service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(MimeMapManager $mime_map_manager, FileSystemInterface $file_system) {
    $this->mimeMapManager = $mime_map_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function guess($path) {
    $extension = '';
    $file_parts = explode('.', $this->fileSystem->basename($path));

    // Remove the first part: a full filename should not match an extension.
    array_shift($file_parts);

    // Iterate over the file parts, trying to find a match.
    // For my.awesome.image.jpeg, we try:
    //   - jpeg
    //   - image.jpeg, and
    //   - awesome.image.jpeg
    while ($additional_part = array_pop($file_parts)) {
      $extension = strtolower($additional_part . ($extension ? '.' . $extension : ''));
      if ($mime_map_extension = $this->mimeMapManager->getExtension($extension)) {
        return $mime_map_extension->getDefaultType(FALSE);
      }
    }

    return 'application/octet-stream';
  }

  /**
   * Sets the mimetypes/extension mapping to use when guessing mimetype.
   *
   * @param array|null $mapping
   *   Passing a NULL mapping will cause guess() to use self::$defaultMapping.
   */
  public function setMapping(array $mapping = NULL) {
    // @todo shall we do something?
    return;
  }

}
