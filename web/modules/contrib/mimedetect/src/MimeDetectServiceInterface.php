<?php

namespace Drupal\mimedetect;

use Drupal\file\FileInterface;

/**
 * MimeDetect service.
 */
interface MimeDetectServiceInterface {

  /**
   * Get the MIME type for a given file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file to be analyzed.
   *
   * @return string
   *   The detected MIME type for the given file.
   */
  public function getMime(FileInterface $file);

}
