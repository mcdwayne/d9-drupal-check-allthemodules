<?php

namespace Drupal\Tests\active_cache\Functional\Plugin\ActiveCache;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;

/**
 * @group active_cache
 */
class CacheRebuildOnTerminateTest extends BrowserTestBase {

  protected $data;

  /**
   * @var \Drupal\active_cache\Plugin\ActiveCacheInterface
   */
  protected $activeCache;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyvalue;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBin;

  public static $modules = [
    'active_cache',
    'active_cache_test'
  ];



  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->activeCache = $this->container->get('plugin.manager.active_cache')
      ->getInstance(['id' => 'simple_database']);
    $this->keyvalue = $this->container->get('keyvalue')
      ->get($this->activeCache->getPluginId());
    $this->cacheBin = $this->container->get('cache.default');
  }

  /**
   * Make sure that the caches are rebuilt on page requests.
   */
  public function testRebuild() {
    // Step 1 - Setup the cached data.
    $this->changeCurrentData();
    $this->activeCache->buildCache();

    // Step 2 - Make sure that data was cached.
    $cache = $this->cacheBin->get($this->activeCache->getCacheId());
    $this->assertNotFalse($cache, 'Data was returned from the cache.');

    // Step 3 - Invalidate the cache and make sure it was invalidated.
    Cache::invalidateTags([$this->activeCache->getPluginId()]);
    $cache = $this->cacheBin->get($this->activeCache->getCacheId());
    $this->assertFalse($cache, 'No data was returned from the cache.');

    // Step 4 - Go to any page and make sure data is cached again.
    $this->drupalGet('/user/login');
    $cache = $this->cacheBin->get($this->activeCache->getCacheId());
    $this->assertNotFalse($cache, 'Data was returned from the cache.');
  }

  protected function changeCurrentData() {
    $data = array_map([$this->getRandomGenerator(), 'object'], range(1, 128));
    $this->keyvalue->setMultiple($data);
  }

  public function dataCallback() {
    return $this->keyvalue->getAll();
  }

}