<?php

namespace Drupal\json_ld_schema\Source;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;

/**
 * Base class for JSON source plugins.
 */
abstract class JsonLdSourceBase extends PluginBase implements JsonLdSourceInterface {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    // Display on every page by default.
    return TRUE;
  }

  /**
   * Get a URI as an absolute string.
   *
   * @param string $uri
   *   A URI.
   *
   * @return string
   *   An absolute URL string.
   */
  protected function absoluteUriString($uri) {
    return Url::fromUri($uri)->setAbsolute(TRUE)->toString();
  }

  /**
   * Format a timestamp according to ISO-8601.
   *
   * @param int $timestamp
   *   A timestamp.
   *
   * @return string
   *   A date timestamp.
   */
  protected function formatTimestamp($timestamp) {
    return date('c', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    return new CacheableMetadata();
  }

}
