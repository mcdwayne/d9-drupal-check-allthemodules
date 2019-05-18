<?php

namespace Drupal\Tests\mimeinfo\Unit\File\MimeType;

/**
 * Class SupportedMimeTypeGuesser.
 *
 * Dummy guesser implementation to test that "isSupported" method allow it
 * for usage.
 */
class SupportedMimeTypeGuesser extends UnsupportedMimeTypeGuesser {

  /**
   * {@inheritdoc}
   */
  public static function isSupported() {
    return TRUE;
  }

}
