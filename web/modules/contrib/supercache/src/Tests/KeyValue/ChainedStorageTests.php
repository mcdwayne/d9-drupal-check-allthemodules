<?php

namespace Drupal\supercache\Tests\KeyValue;

use Drupal\supercache\Tests\Generic\KeyValue\KeyValueTests as KeyValueTests;

use Drupal\supercache\KeyValueStore\KeyValueChainedFactory;
use Drupal\supercache\Tests\Generic\Cache\CacheServicesTrait;

/**
 * Test this on top of different combinations of cache backends.
 */
class ChainedStorageTests extends KeyValueTests {

  use CacheServicesTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * List of cache backend services.
   * 
   * @var string[]
   */
  protected $backends;

  public function setUp() {

    parent::setUp();
    $this->installSchema('system', ['key_value']);

    $this->backends = $this->populateCacheServices();

  }

  public function testKeyValue() { 
  
    // The good thing of this is that besides testing
    // the storage, we also test ALL the cache backend
    // implementations.
    foreach ($this->backends as $backend) {
      $connection = \Drupal\Core\Database\Database::getConnection();
      $factory = $this->container->get($backend);
      $serializer = new \Drupal\Component\Serialization\PhpSerialize();
      $this->factory = new KeyValueChainedFactory($factory, $serializer, $connection);
      // Call the real test.
      parent::testKeyValue();
    }
  
  }

  public function tearDown() {

  }
}
