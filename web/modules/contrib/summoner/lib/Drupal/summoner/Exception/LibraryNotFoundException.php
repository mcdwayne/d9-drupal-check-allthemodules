<?php
/**
 * @file
 * Contains Drupal\summoner\Exception\LibraryNotFoundException.
 */

namespace Drupal\summoner\Exception;

/**
 * Class LibraryNotFoundException
 */
class LibraryNotFoundException extends \Exception {
  public function __construct($libraries) {
    $message = 'The libraries ' . implode(', ', $libraries) . ' do not exist.';
    parent::__construct($message, 0, NULL);
  }
}