<?php

namespace Drupal\Tests\active_cache\Kernel\Plugin\ActiveCache;

use Drupal\active_cache_test\Plugin\ActiveCache\CustomCallbackTest;
use Drupal\Core\Cache\Cache;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @group active_cache
 */
class ActiveCacheStressTest extends KernelTestBase {

  public static $modules = [
    'active_cache',
    'active_cache_test',
    'database_test'
  ];

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->database = $this->container->get('database');

    $this->installSchema('database_test', ['test_people']);
  }

  /**
   * @param callable $callback
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param string $id
   * @param array $definition
   * @return \Drupal\active_cache\Plugin\ActiveCacheInterface
   */
  protected function createActiveCache(callable $callback, ContainerInterface $container, $id = 'custom_callback_test', $definition = []) {
    $definition += [
      'id' => $id,
      'label' => $id,
      'cache_id' => $id,
      'cache_tags' => [$id],
      'cache_bin' => 'default',
      'max_age' => Cache::PERMANENT
    ];
    $configuration = [
      'data_callback' => $callback
    ];

    return CustomCallbackTest::create($container, $configuration, $id, $definition);
  }

  /**
   * The active cache plugins should return cached data until it is invalidated.
   * After invalidation new data should be fetched.
   */
  public function testInvalidationAndDataConsistency() {
    // Step 1 - Add sample data, setup the callback method and the active cache plugin.
    $this->addSampleData();
    $callback = [$this, 'selectPersonByJob'];
    $active_cache = $this->createActiveCache($callback, $this->container);

    // Step 2 - Validate the initial results match.
    $this->assertEquals(call_user_func($callback), $active_cache->getData(), 'ActiveCache::getData method returns expected data.');

    // Step 3 - Add some more data, Validate that the cached data is out of date.
    $this->addSampleData();
    $this->assertNotEquals(call_user_func($callback), $active_cache->getData(), 'Data is outdated and ActiveCache::getData method should return the old results.');

    // Step 4 - Invalidate the caches, See that the active cache returns the correct data.
    Cache::invalidateTags($active_cache->getCacheTags());
    $this->assertEquals(call_user_func($callback), $active_cache->getData(), 'New data was fetched and the results match.');
  }

  /**
   * A poorly made select query that will take some time to return a value.
   * @return array
   */
  public function selectPersonByJob() {
    $jobs = $this->database->select('test_people')
      ->fields('test_people', ['job'])
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    $result = [];

    foreach ($jobs as $job) {
      $query = $this->database->select('test_people')
        ->fields('test_people', ['name', 'age']);
      $query->condition('job', $job['job']);
      $result[$job['job']] = (array) $query->execute()->fetchCol();
    }

    return $result;
  }

  protected function stopwatch($callback, &$time) {
    $start = microtime(TRUE);
    $return = call_user_func($callback);
    $time = microtime(TRUE) - $start;
    return $return;
  }

  protected function addSampleData($size = 1000) {
    foreach (range(1, $size) as $i) {
      $this->database->insert('test_people')
        ->fields([
          'name' => $this->getRandomGenerator()->name(8, TRUE),
          'age' => rand(0, 128),
          'job' => $this->getRandomGenerator()->name(8)
        ])
        ->execute();
    }
  }
}