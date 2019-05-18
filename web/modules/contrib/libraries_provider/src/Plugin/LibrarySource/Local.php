<?php

namespace Drupal\libraries_provider\Plugin\LibrarySource;

/**
 * Provides a plugin for libraries in the local filesystem.
 *
 * @LibrarySource(
 *   id = "local"
 * )
 */
class Local extends LibrarySourceBase {

  /**
   * {@inheritdoc}
   */
  public function getAvailableVersions(string $libraryId) {
    $version = json_decode(file_get_contents($this->getLocalPath($libraryId) . '/package.json'))->version;
    return [$version => $version];
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable($libraryId): bool {
    $localPath = $this->getLocalPath($libraryId);
    if (file_exists($localPath) && is_dir($localPath)) {
      return TRUE;
    }

    list($extension, $libraryName) = explode('__', $libraryId);
    $this->availabilityMessages[$libraryId] = '<p>' .
      $this->t('To use a local version of "@libraryName"(defined at "@extension.libraries.yml"), download it  and extract it to "@localPath".', [
        '@libraryName' => $libraryName,
        '@extension' => $extension,
        '@localPath' => $localPath,
      ]) .
      '</p>';
    return FALSE;
  }

  /**
   * Returns the path where the library is expected.
   */
  protected function getLocalPath($libraryId) {
    $library = $this->getLibrary($libraryId);
    $localName = $this->getLocalName($library['libraries_provider']['npm_name']);
    return DRUPAL_ROOT . '/libraries/' . $localName;
  }

  /**
   * Replace non-valid characters.
   *
   * Follow the same replacements as https://asset-packagist.orocrm.com/ .
   */
  protected function getLocalName($name) {
    return str_replace([
      '@',
      '/',
    ], [
      '',
      '--',
    ], $name);

  }

  /**
   * {@inheritdoc}
   */
  public function getCanonicalPath(string $path) {
    // Remove libraries path and packagename.
    $path = preg_replace('~^/libraries/[a-zA-Z0-9\-]+~', '', $path);
    // Remove min suffix.
    $path = preg_replace('~\.min(\.[a-z]+)$~', '${1}', $path);

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(string $canonicalPath, array $library) {
    $canonicalPath = $this->applyVariants($canonicalPath, $library);
    $localName = $this->getLocalName($library['libraries_provider']['npm_name']);
    $path = '/libraries/' .
      $localName .
      $canonicalPath;
    if ($library['libraries_provider']['serve_minified']) {
      $path = preg_replace('~(\.[a-z]+)$~', '.min${1}', $path);
    }
    return $path;
  }

}
