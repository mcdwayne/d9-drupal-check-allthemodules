<?php

namespace Drupal\supercache\Tests\Generic\Cache;

use Drupal\couchbasedrupal\CouchbaseManager;

use Drupal\couchbasedrupal\Cache\CouchbaseRawBackendFactory;
use Drupal\wincachedrupal\Cache\WincacheRawBackendFactory;
use Drupal\couchbasedrupal\Cache\CouchbaseBackendFactory;
use Drupal\wincachedrupal\Cache\WincacheBackendFactory;

use Drupal\supercache\Cache\ApcuRawBackendFactory;
use Drupal\supercache\Cache\DatabaseRawBackendFactory;
use Drupal\supercache\Cache\ChainedFastRawBackendFactory;
use Drupal\supercache\Cache\ChainedFastBackendFactory;

use Drupal\Core\Cache\ApcuBackendFactory;
use Drupal\Core\Cache\DatabaseBackendFactory;
use Drupal\Core\Site\Settings;

/**
 * Use this to populate the container
 * with a wide set of cache services
 * that can be used to test components that
 * depend on caches.
 */
trait CacheServicesTrait {
  
  /**
   * Populates the container with a wide sample of cache
   * factory services to test componentes that depend
   * on caches.
   *
   * \Drupal\supercache\Cache\CacheRawFactoryInterface
   * 
   * @return string[]
   *   The name of the populated services.
   */
  protected function populateRawCacheServices() {

    /** @var \Psr\Log\LoggerInterface */
    $logger =  $this->getMock(\Psr\Log\LoggerInterface::class);
    $app_root = '/';
    $site_path = 'mypath';

    // The testing system chokes
    // when database used during shutdown.
    new Settings(
      ['chained_disable_shutdown' => TRUE]
      );

    $settings = \Drupal\Core\Site\Settings::getInstance();
    $manager = new CouchbaseManager($settings, $logger);
    $connection = \Drupal\Core\Database\Database::getConnection();


    $this->container->set('rawbackend.wincache', new WincacheRawBackendFactory($app_root, $site_path));
    $this->container->set('rawbackend.couchbase', new CouchbaseRawBackendFactory($manager, $app_root, $site_path));
    $this->container->set('rawbackend.apcu', new ApcuRawBackendFactory($app_root, $site_path));
    $this->container->set('rawbackend.database', new DatabaseRawBackendFactory($connection));

    // Wincache + Couchbase
    $f = new ChainedFastRawBackendFactory($settings, 'rawbackend.couchbase', 'rawbackend.wincache');
    $f->setContainer($this->container);
    $this->container->set('rawbackend.chained.couchbase_wincache', $f);

    // Wincache + Database
    $f = new ChainedFastRawBackendFactory($settings, 'rawbackend.database', 'rawbackend.wincache');
    $f->setContainer($this->container);
    $this->container->set('rawbackend.chained.database_wincache', $f);

    // Apcu + Couchbase
    $f = new ChainedFastRawBackendFactory($settings, 'rawbackend.couchbase', 'rawbackend.apcu');
    $f->setContainer($this->container);
    $this->container->set('rawbackend.chained.couchbase_apcu', $f);

    // Apcu + Database
    $f = new ChainedFastRawBackendFactory($settings, 'rawbackend.database', 'rawbackend.apcu');
    $f->setContainer($this->container);
    $this->container->set('rawbackend.chained.database_apcu', $f);

    // Couchbase + Database
    $f = new ChainedFastRawBackendFactory($settings, 'rawbackend.database', 'rawbackend.couchbase');
    $f->setContainer($this->container);
    $this->container->set('rawbackend.chained.database_couchbase', $f);

    return [
        'rawbackend.wincache',
        'rawbackend.couchbase',
        'rawbackend.apcu',
        'rawbackend.database',
        'rawbackend.chained.couchbase_wincache',
        'rawbackend.chained.database_wincache',
        'rawbackend.chained.couchbase_apcu',
        'rawbackend.chained.database_apcu',
        'rawbackend.chained.database_couchbase'
      ];
  }

  /**
   * Populates the container with a wide sample of cache
   * factory services to test components that
   * depend on caches.
   *
   * \Drupal\Core\Cache\CacheFactoryInterface
   *
   * @return string[]
   *   The name of the populated services.
   */
  protected function populateCacheServices() {

    /** @var \Psr\Log\LoggerInterface */
    $logger =  $this->getMock(\Psr\Log\LoggerInterface::class);
    $app_root = '/';
    $site_path = 'mypath';

    // The testing system chokes
    // when database used during shutdown.
    new Settings(
      ['chained_disable_shutdown' => TRUE]
      );

    $settings = \Drupal\Core\Site\Settings::getInstance();
    $manager = new CouchbaseManager($settings, $logger);
    $connection = \Drupal\Core\Database\Database::getConnection();

    $checksum = new \Drupal\supercache\Cache\DummyTagChecksum();

    $this->container->set('backend.wincache', new WincacheBackendFactory($app_root, $site_path, $checksum));
    $this->container->set('backend.couchbase', new CouchbaseBackendFactory($manager, $app_root, $site_path, $checksum, TRUE));
    $this->container->set('backend.apcu', new ApcuBackendFactory($app_root, $site_path, $checksum));
    $this->container->set('backend.database', new DatabaseBackendFactory($connection, $checksum));

    // Wincache + Couchbase
    $f = new ChainedFastBackendFactory($settings, 'backend.couchbase', 'backend.wincache');
    $f->setContainer($this->container);
    $this->container->set('backend.chained.couchbase_wincache', $f);

    // Wincache + Database
    $f = new ChainedFastBackendFactory($settings, 'backend.database', 'backend.wincache');
    $f->setContainer($this->container);
    $this->container->set('backend.chained.database_wincache', $f);

    // Apcu + Couchbase
    $f = new ChainedFastBackendFactory($settings, 'backend.couchbase', 'backend.apcu');
    $f->setContainer($this->container);
    $this->container->set('backend.chained.couchbase_apcu', $f);

    // Apcu + Database
    $f = new ChainedFastBackendFactory($settings, 'backend.database', 'backend.apcu');
    $f->setContainer($this->container);
    $this->container->set('backend.chained.database_apcu', $f);

    // Couchbase + Database
    $f = new ChainedFastBackendFactory($settings, 'backend.database', 'backend.couchbase');
    $f->setContainer($this->container);
    $this->container->set('backend.chained.database_couchbase', $f);

    return [
        'backend.wincache',
        'backend.couchbase',
        'backend.apcu',
        'backend.database',
        'backend.chained.couchbase_wincache',
        'backend.chained.database_wincache',
        'backend.chained.couchbase_apcu',
        'backend.chained.database_apcu',
        'backend.chained.database_couchbase'
      ];
  }

}
