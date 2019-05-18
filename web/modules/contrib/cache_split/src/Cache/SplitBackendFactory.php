<?php
/**
 * @file
 * Contains \Drupal\cache_split\Cache\SplitBackendFactory.
 */

namespace Drupal\cache_split\Cache;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Factory for cache split backend.
 */
class SplitBackendFactory implements CacheFactoryInterface {

  use ContainerAwareTrait;

  /**
   * @var array
   */
  protected $cache_split_settings = [];

  /**
   * @var \Drupal\cache_split\Cache\SplitBackend[]
   */
  protected $bins;

  /**
   * SplitBackendFactory constructor.
   *
   * @param \Drupal\Core\Site\Settings $settings
   */
  public function __construct(Settings $settings) {
    $this->cache_split_settings = $settings->get('cache_split', []);
  }

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    // Reuse generated backends to avoid reinitializations.
    if (!isset($this->bins[$bin])) {
      $collection = $this->getMatchers($bin);
      $this->bins[$bin] = new SplitBackend($collection);
    }
    return $this->bins[$bin];
  }

  /**
   * Get collection of matcher items.
   *
   * @param string $bin
   *
   * @return \Drupal\cache_split\Cache\CacheBackendMatcherCollection
   */
  protected function getMatchers($bin) {
    // When no configuration is given fallback to the default.
    if (empty($this->cache_split_settings[$bin])) {
      $this->cache_split_settings[$bin] = [];
    }

    $collection = new CacheBackendMatcherCollection();
    $has_default = FALSE;
    foreach ($this->cache_split_settings[$bin] as $key => $config) {
      $config += [
        'backend' => $key,
        'includes' => [],
        'excludes' => [],
      ];
      $backend = $this->getCacheBackend($bin, $config['backend']);
      $matcher = new CacheBackendMatcher($backend, $config);
      // In case this is no fallback matcher we can add it to the collection.
      if (!$matcher->isFallback()) {
        $collection->add($matcher);
      }
      // Otherwise we only set the first fallbac
      elseif (!$has_default) {
        $collection->setFallbackMatcher($matcher);
        $has_default = TRUE;
        // Any additional matcher will be skipped, as they are defined after the
        // fallback matcher.
        break;
      }
    }

    // In case no default matcher was configured, we set the database backend.
    if (!$has_default) {
      $collection->setFallbackBackend($this->getCacheBackend($bin, 'cache.backend.database'));
    }

    return $collection;
  }

  /**
   * Create cache backend for bin from the given config array.
   *
   * @param string $bin
   *   Holds cache bin name to create the backend for.
   * @param string $backend
   *   Holds the name of the backend factory service.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend initialised for this bin.
   *
   * @throws \Exception
   */
  protected function getCacheBackend($bin, $backend) {
    $factory = $this->container->get($backend);
    // Check if we got a cache factory here.
    if (!$factory instanceof CacheFactoryInterface) {
      throw new \Exception(sprintf('Services "%s" does not implement CacheFactoryInterface', $backend));
    }

    return $factory->get($bin);
  }
}
