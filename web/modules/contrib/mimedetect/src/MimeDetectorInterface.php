<?php

namespace Drupal\mimedetect;

/**
 * An interface for all MimeDetector type plugins.
 */
interface MimeDetectorInterface {

  /**
   * Provide a description of the detector.
   *
   * @return string
   *   The MIME detector description.
   */
  public function description();

  /**
   * Try MIME detection on a given file.
   *
   * @param string $path
   *   Path of the file to be analyzed.
   *
   * @return string
   *   The detected MIME, NULL if file contents are not recognized.
   */
  public function detect($path);

}
