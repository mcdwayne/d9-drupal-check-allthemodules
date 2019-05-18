<?php

namespace Drupal\mimeinfo\File\MimeType;

use Drupal\Core\File\MimeType\MimeTypeGuesser as BaseMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Class MimeTypeGuesser.
 */
class MimeTypeGuesser extends BaseMimeTypeGuesser {

  /**
   * {@inheritdoc}
   */
  public function addGuesser(MimeTypeGuesserInterface $guesser, $priority = 0) {
    // Symfony's guessers has non-interfaced "isSupported" method to check that
    // environment supports guessing mechanism. Allow all guessers define same
    // the method for same purposes. Otherwise consider that guesser is allowed
    // to use.
    // @see \Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser::isSupported()
    if (method_exists($guesser, 'isSupported') ? $guesser::isSupported() : TRUE) {
      parent::addGuesser($guesser, $priority);
    }

    return $this;
  }

}
