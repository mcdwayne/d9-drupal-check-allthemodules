<?php

namespace Drupal\drd;

/**
 * Class LibraryBuild.
 *
 * @package Drupal\drd
 */
class LibraryBuild {

  const DRD_LIBRARY_VERSION = '1.7.0';

  /**
   * Build a current version of the library.
   *
   * @param array $arguments
   *   Arguments for the build process.
   */
  public function build(array &$arguments) {
    $archive = 'drd-' . self::DRD_LIBRARY_VERSION . '.phar';
    $path = 'public://' . $archive;
    if (isset($arguments['source']) && $arguments['source'] == 'local') {
      $arguments['url'] = file_create_url($path);
    }

    $root = drupal_get_path('module', 'drd');
    $phar = new \Phar(\Drupal::service('file_system')->realpath($path), 0, $archive);
    $phar->buildFromDirectory($root . '/src/Agent');
    $phar->buildFromDirectory($root . '/src/Crypt');
    $phar->setStub($phar->createDefaultStub('index.php'));

    if (PHP_SAPI === 'cli') {
      // We can't determine our own URL and hence have to deliver the content
      // of the file as part of the request.
      $arguments['lib'] = base64_encode(file_get_contents($path));
    }
  }

}
