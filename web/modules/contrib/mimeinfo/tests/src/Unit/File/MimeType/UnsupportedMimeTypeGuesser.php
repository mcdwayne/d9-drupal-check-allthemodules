<?php

namespace Drupal\Tests\mimeinfo\Unit\File\MimeType;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Class UnsupportedMimeTypeGuesser.
 *
 * Dummy guesser implementation to test that unsupported guesser will not
 * be used for guessing the MIME type of file.
 */
class UnsupportedMimeTypeGuesser implements MimeTypeGuesserInterface {

  /**
   * Check that environment supports guessing mechanism.
   *
   * @return bool
   *   Whether environment supports guessing mechanism.
   */
  public static function isSupported() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function guess($path) {
    return NULL;
  }

}
