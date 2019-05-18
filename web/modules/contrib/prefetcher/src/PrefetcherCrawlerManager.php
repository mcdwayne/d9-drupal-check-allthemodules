<?php

namespace Drupal\prefetcher;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages image toolkit plugins.
 *
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkit
 * @see \Drupal\Core\ImageToolkit\ImageToolkitInterface
 * @see \Drupal\Core\ImageToolkit\ImageToolkitBase
 * @see plugin_api
 */
class PrefetcherCrawlerManager extends DefaultPluginManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the PrefetcherCrawlerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/PrefetcherCrawler', $namespaces, $module_handler, 'Drupal\prefetcher\CrawlerInterface', 'Drupal\prefetcher\Annotation\Crawler');

    $this->setCacheBackend($cache_backend, 'prefetcher_crawler');
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the default crawler ID.
   *
   * @return string|bool
   *   ID of the default toolkit, or FALSE on error.
   */
  public function getDefaultCrawlerId() {
    return 'prefetcher_crawler_basic';
  }

  /**
   * Gets the default image toolkit.
   *
   * @return \Drupal\prefetcher\CrawlerInterface
   *   Object of the default crawler, or FALSE on error.
   */
  public function getDefaultCrawler() {
    if ($crawler_id = $this->getDefaultCrawlerId()) {
      return $this->getPlugin($crawler_id);
    }
    return FALSE;
  }

  public function getPlugin($plugin_id = NULL) {
    $plugin_id = $plugin_id ?: $this->config->get($this->getType() . '.plugin_id');
    $plugins = $this->getAvailablePlugins();

    // Check if plugin is available.
    if (!isset($plugins[$plugin_id]) || !class_exists($plugins[$plugin_id]['class'])) {
      trigger_error("prefetcher handling plugin '$plugin_id' is no longer available.", E_USER_ERROR);
      $plugin_id = NULL;
    }

    return $this->createInstance($plugin_id);
  }

  /**
   * Gets a list of available crawler.
   *
   * @return array
   *   An array with the toolkit names as keys and the descriptions as values.
   */
  public function getAvailablePlugins() {
    // Use plugin system to get list of available toolkits.
    $crawler = $this->getDefinitions();

    $output = array();
    foreach ($crawler as $id => $definition) {
      // Only allow modules that aren't marked as unavailable.
      if (call_user_func($definition['class'] . '::isAvailable')) {
        $output[$id] = $definition;
      }
    }
    return $output;
  }
}
