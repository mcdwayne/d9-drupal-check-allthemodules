<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\ChainedFastBackendFactory.
 */

namespace Drupal\supercache\Cache;

use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;


use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Defines the chained fast cache backend factory.
 */
class ChainedFastRawBackendFactory implements CacheRawFactoryInterface, EventSubscriberInterface {

  use ContainerAwareTrait;

  /**
   * The service name of the consistent backend factory.
   *
   * @var string
   */
  protected $consistentServiceName;

  /**
   * The service name of the fast backend factory.
   *
   * @var string
   */
  protected $fastServiceName;

  /**
   * Cache binary instances.
   *
   * @var ChainedFastRawBackend[]
   */
  protected $caches;

  /**
   * Track wether the Kernel is terminated.
   *
   * @var bool
   */
  protected $kernel_terminated;

  /**
   * Constructs ChainedFastBackendFactory object.
   *
   * @param Settings|NULL $settings
   *   (optional) The settings object.
   * @param string|NULL $consistent_service_name
   *   (optional) The service name of the consistent backend factory. Defaults
   *   to:
   *   - $settings->get('cache')['default'] (if specified)
   *   - 'cache.backend.database' (if the above isn't specified)
   * @param string|NULL $fast_service_name
   *   (optional) The service name of the fast backend factory. Defaults to:
   *   - 'cache.backend.apcu' (if the PHP process has APCu enabled)
   *   - NULL (if the PHP process doesn't have APCu enabled)
   */
  public function __construct(Settings $settings = NULL, $consistent_service_name = NULL, $fast_service_name = NULL) {
    // Default the consistent backend to the site's default backend.
    if (!isset($consistent_service_name)) {
      $cache_settings = isset($settings) ? $settings->get('rawcache') : array();
      $consistent_service_name = isset($cache_settings['default']) ? $cache_settings['default'] : 'cache.rawbackend.database';
    }

    // Default the fast backend to APCu if it's available.
    if (!isset($fast_service_name) && function_exists('apcu_fetch')) {
      $fast_service_name = 'cache.rawbackend.apcu';
    }

    $this->consistentServiceName = $consistent_service_name;
    $this->fastServiceName = $fast_service_name;
    $this->caches = [];
    $this->kernel_terminated = FALSE;
  }

  /**
   * Instantiates a chained, fast cache backend class for a given cache bin.
   *
   * @param string $bin
   *   The cache bin for which a cache backend object should be returned.
   *
   * @return CacheRawBackendInterface
   *   The cache backend object associated with the specified bin.
   */
  public function get($bin) {
    // Use the chained backend only if there is a fast backend available;
    // otherwise, just return the consistent backend directly.
    if (isset($this->fastServiceName)) {
      return new ChainedFastRawBackend(
        $this->container->get($this->consistentServiceName)->get($bin),
        $this->container->get($this->fastServiceName)->get($bin),
        !$this->kernel_terminated
      );
    }
    else {
      return $this->container->get($this->consistentServiceName)->get($bin);
    }
  }

  /**
   * Shutdown functions.
   *
   * Using __destruct() proved to be problematic
   * with some some cache backends such as couchbase
   * with custom transcoders or the Drupal.org
   * test bot.
   *
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    foreach ($this->caches as $cache) {
      $cache->onKernelTerminate();
    }
    $this->kernel_terminated = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = array('onKernelTerminate', -100);
    return $events;
  }

}
