<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\Core\Cache\CacheFactory;
use Drupal\Core\Site\Settings;

/**
 * Class CacheConsistentFactory.
 *
 * @package Drupal\cache_consistent\Cache
 *
 * @ingroup cache_consistent
 */
class CacheConsistentFactory extends CacheFactory implements CacheConsistentFactoryInterface {

  use CacheTagsChecksumAwareTrait;

  /**
   * A map of cache backends that are intrinsically consistent.
   *
   * @var array
   */
  protected $defaultConsistentBackends;

  /**
   * Consistent backend singletons.
   *
   * @var CacheConsistentBackend[]
   */
  static protected $backends = [];

  /**
   * Constructs CacheFactory object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings array.
   * @param array $default_bin_backends
   *   (optional) A mapping of bin to backend service name. Mappings in
   *   $settings take precedence over this.
   */
  public function __construct(Settings $settings, array $default_bin_backends = array(), array $default_consistent_backends = array()) {
    parent::__construct($settings, $default_bin_backends);
    $this->defaultConsistentBackends = $default_consistent_backends;
  }

  /**
   * Gets CacheConsistent for the specified cache bin.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\cache_consistent\Cache\CacheConsistentBackend
   *   The cache consistent backend object for the specified cache bin.
   */
  public function get($bin) {
    // Get real cache backend.
    $service_name = $this->getServiceName($bin);
    $backend = $this->container->get($service_name)->get($bin);

    // Check if service is consistent.
    if ($this->isServiceConsistent($service_name)) {
      return $backend;
    }

    // Cache backend is not intrinsically consistent. Let's buffer the cache
    // operations during transactions.
    try {
      if (!isset(static::$backends[$bin])) {
        $cache_settings = $this->settings->get('cache');
        $buffer = $this->container->get('cache_consistent.buffer_factory')
          ->get($bin, $backend);
        $isolation_level = isset($cache_settings['isolation_level']) ? $cache_settings['isolation_level'] : 2;
        static::$backends[$bin] = new CacheConsistentBackend($buffer, $this->checksumProvider, $isolation_level);
      }
      return static::$backends[$bin];
    }
    catch (\Exception $e) {
      // Something went wrong. Log it, and let's just return the backend, so
      // that the site at least "works".
      /* @var \Psr\Log\LoggerInterface $logger */
      if ($logger = $this->container->has('logger.factory') ? $this->container->get('logger.factory')->get('cache_consistent') : NULL) {
        $logger->error($e->getMessage());
      }
      return $backend;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceName($bin) {
    $cache_settings = $this->settings->get('cache');
    if (isset($cache_settings['bins'][$bin])) {
      $service_name = $cache_settings['bins'][$bin];
    }
    elseif (isset($cache_settings['default'])) {
      $service_name = $cache_settings['default'];
    }
    elseif (isset($this->defaultBinBackends[$bin])) {
      $service_name = $this->defaultBinBackends[$bin];
    }
    else {
      $service_name = 'cache.backend.database';
    }
    return $service_name;
  }

  /**
   * {@inheritdoc}
   */
  public function isServiceConsistent($service_name) {
    $cache_settings = $this->settings->get('cache');
    // Just return backend if cache consistent is not even enabled.
    if (!empty($cache_settings['consistent'])) {
      return TRUE;
    }

    // Check for intrinsically consistent cache backends.
    if (isset($cache_settings['consistent_backends'][$service_name])) {
      // If the cache backend is manually configured to be consistent, then just
      // return the backend.
      if (!empty($cache_settings['consistent_backends'][$service_name])) {
        return TRUE;
      }
    }
    elseif (!empty($this->defaultConsistentBackends[$service_name])) {
      // Check default settings.
      return TRUE;
    }

    return FALSE;
  }

}
