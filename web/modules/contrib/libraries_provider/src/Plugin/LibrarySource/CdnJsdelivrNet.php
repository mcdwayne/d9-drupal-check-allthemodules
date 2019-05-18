<?php

namespace Drupal\libraries_provider\Plugin\LibrarySource;

use Upstreamable\JsdelivrApiClient\JsdelivrApiClientBuilder;
use Upstreamable\JsdelivrApiClient\Exception\NotFoundHttpException;

/**
 * Provides a plugin for getting libraries from the Jsdelivr CDN.
 *
 * Only npm releases are supported at the moment.
 *
 * @LibrarySource(
 *   id = "cdn.jsdelivr.net"
 * )
 */
class CdnJsdelivrNet extends LibrarySourceBase {

  const API_URI = 'https://data.jsdelivr.com/v1/';
  const PACKAGE_SOURCE = 'npm';
  const CDN_URI = 'https://cdn.jsdelivr.net/';

  protected $client;

  /**
   * {@inheritdoc}
   */
  public function getAvailableVersions(string $libraryId) {
    $library = $this->getLibrary($libraryId);

    $client = $this->getClient();

    $versions = $client->getVersionsApi()->getVersions($library['libraries_provider']['npm_name']);
    $versions = array_combine($versions, $versions);

    foreach ($library['libraries_provider']['blacklist_releases'] as $version) {
      unset($versions[$version]);
    }

    return $versions;
  }

  /**
   * Returns a client instance.
   */
  protected function getClient() {
    if ($this->client) {
      return $this->client;
    }
    $this->client = (new JsdelivrApiClientBuilder(
      static::API_URI,
      static::PACKAGE_SOURCE,
      static::CDN_URI
    ))->buildClient();
    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function getCanonicalPath(string $path) {
    // Remove the protocol, domain and package source.
    $path = str_replace(static::CDN_URI . static::PACKAGE_SOURCE . '/', '', $path);
    // Remove packacename@version.
    $path = preg_replace('~[a-zA-Z0-9\@\-\/]+@\d\.\d\.\d~', '', $path);
    // Remove min suffix.
    $path = preg_replace('~\.min(\.[a-z]+)$~', '${1}', $path);

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(string $canonicalPath, array $library) {
    $canonicalPath = $this->applyVariants($canonicalPath, $library);
    $path = static::CDN_URI . static::PACKAGE_SOURCE . '/' .
      $library['libraries_provider']['npm_name'] . '@' .
      $library['version'] .
      $canonicalPath;
    if ($library['libraries_provider']['serve_minified']) {
      $path = preg_replace('~(\.[a-z]+)$~', '.min${1}', $path);
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(string $libraryId): bool {
    $library = $this->getLibrary($libraryId);

    $client = $this->getClient();

    try {
      $versions = $client->getVersionsApi()->getVersions($library['libraries_provider']['npm_name']);
    }
    catch (NotFoundHttpException $exception) {
      list($extension, $libraryName) = explode('__', $libraryId);
      $this->availabilityMessages[$libraryId] = '<p>' .
        $this->t('NOTICE: the library "@libraryName" provided by the theme or module "@extension" is not available in the NPM registry so JsDelivr can not be used.', [
          '@extension' => $extension,
          '@libraryName' => $libraryName,
        ]) .
        '</p>';
      return FALSE;
    }
    return TRUE;
  }

}
