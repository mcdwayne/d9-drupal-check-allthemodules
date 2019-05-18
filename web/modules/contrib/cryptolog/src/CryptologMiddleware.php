<?php

namespace Drupal\cryptolog;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\ApcuBackendFactory;
use Drupal\Core\Cache\ChainedFastBackendFactory;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Cryptolog middleware.
 */
class CryptologMiddleware implements HttpKernelInterface {

  /**
   * The cache bin.
   */
  const BIN = 'bootstrap';

  /**
   * The cache identifier.
   */
  const KEY = 'cryptolog';

  /**
   * The cache time to live in seconds.
   */
  const TTL = 86400;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $backend;

  /**
   * The cached salt and day of year.
   *
   * @var array
   */
  protected $cache;

  /**
   * The original client IP address.
   *
   * @var string|null
   */
  protected $clientIp;

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs the Cryptolog middleware.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings object.
   * @param \Drupal\Core\Cache\ChainedFastBackendFactory $chained_fast
   *   Chained fast cache backend factory.
   * @param \Drupal\Core\Cache\ApcuBackendFactory $apcu
   *   APCu cache backend factory.
   * @param \Drupal\memcache\MemcacheBackendFactory|null $memcache
   *   (optional) Memcache cache backend factory.
   * @param \Drupal\memcache_storage\MemcachedBackendFactory|null $memcache_storage
   *   (optional) Memcache Storage cache backend factory.
   * @param \Drupal\redis\Cache\CacheBackendFactory|null $redis
   *   (optional) Redis cache backend factory.
   */
  public function __construct(HttpKernelInterface $http_kernel, Settings $settings, ChainedFastBackendFactory $chained_fast, ApcuBackendFactory $apcu, $memcache = NULL, $memcache_storage = NULL, $redis = NULL) {
    $this->httpKernel = $http_kernel;
    $this->setBackend($settings, $chained_fast, $apcu, $memcache, $memcache_storage, $redis);
    $this->cache = $this->getCache();
    $day = gmdate('zY');
    if (!$this->cache || $this->cache['day'] !== $day) {
      $this->cache = ['day' => $day, 'salt' => Crypt::randomBytes(32)];
      $this->setCache($this->cache);
    }
  }

  /**
   * Returns the backend cache bin for diagnostic purposes.
   */
  public function getBackend() {
    return $this->backend;
  }

  /**
   * Fetches salt and day of year from the cache.
   */
  protected function getCache() {
    $cache = $this->backend->get(self::KEY);
    return $cache ? $cache->data : FALSE;
  }

  /**
   * Returns the original client IP address for diagnostic purposes.
   */
  public function getClientIp() {
    return $this->clientIp;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($type === self::MASTER_REQUEST) {
      $this->setClientIp($request);
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Sets the backend cache bin.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings object.
   * @param \Drupal\Core\Cache\ChainedFastBackendFactory $chained_fast
   *   Chained fast cache backend factory.
   * @param \Drupal\Core\Cache\ApcuBackendFactory $apcu
   *   APCu cache backend factory.
   * @param \Drupal\memcache\MemcacheBackendFactory|null $memcache
   *   (optional) Memcache cache backend factory.
   * @param \Drupal\memcache_storage\MemcachedBackendFactory|null $memcache_storage
   *   (optional) Memcache Storage cache backend factory.
   * @param \Drupal\redis\Cache\CacheBackendFactory|null $redis
   *   (optional) Redis cache backend factory.
   */
  protected function setBackend(Settings $settings, ChainedFastBackendFactory $chained_fast, ApcuBackendFactory $apcu, $memcache = NULL, $memcache_storage = NULL, $redis = NULL) {
    // Fall back to chained fast cache backend if APCu extension is disabled.
    if (!extension_loaded('apcu')) {
      $apcu = NULL;
    }
    $backend = $memcache ?: $memcache_storage ?: $redis ?: $apcu ?: $chained_fast;
    // Use chained fast cache backend if default cache backend is memory-based.
    $cache_settings = $settings->get('cache');
    if (isset($cache_settings['default'])) {
      switch ($cache_settings['default']) {
        case 'cache.backend.memcache':
        case 'cache.backend.memcache_storage':
        case 'cache.backend.redis':
          $backend = $chained_fast;
      }
    }
    $this->backend = $backend->get(self::BIN);
  }

  /**
   * Stores salt and day of year in the cache.
   */
  protected function setCache($value) {
    return $this->backend->set(self::KEY, $value, self::TTL + REQUEST_TIME);
  }

  /**
   * Sets the client IP address.
   */
  protected function setClientIp(Request $request) {
    $this->clientIp = $request->getClientIp();
    // Generate a keyed hash in IPv6 address notation.
    $request->server->set('REMOTE_ADDR', inet_ntop(hash_hmac('md5', $this->clientIp, $this->cache['salt'], TRUE)));
  }

}
