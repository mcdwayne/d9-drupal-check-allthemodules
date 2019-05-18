<?php

namespace Drupal\couchbasedrupal;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

use Drupal\Core\Site\Settings;

use Drupal\couchbasedrupal\Cache\CouchbaseBackendFactory;
use Drupal\couchbasedrupal\Cache\CouchbaseTagsChecksum;
use Drupal\couchbasedrupal\Lock\CouchbaseLockBackend;
use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\Core\Lock\PersistentDatabaseLockBackend;
use Drupal\Core\Cache\DatabaseCacheTagsChecksum;

use Couchbase\N1qlQuery;

class CouchbasedrupalServiceProvider implements ServiceProviderInterface {

  /**
   * Log an error registered as a shutdown function
   * as doing so during container rebuild is too dangerous...
   *
   * @param string $message
   */
  protected function logError(string $message, array $context = [], \Throwable $exception = NULL) {
    register_shutdown_function(function() use ($message, $context, $exception) {
      try {
        if (!empty($exception)) {
          $context += \Drupal\Core\Utility\Error::decodeException($exception);
        }
        \Drupal::logger("couchbasedrupal")->error($message, $context);
      }
      catch (\Throwable $e) {}
    });
  }

  /**
   * Disables any couchbase related services
   * and configuration in case it fails to start-up.
   *
   * @param ContainerBuilder $container
   */
  protected function disableRelatedServices(ContainerBuilder $container) {
    // Remove the cache_tags_invalidator tag so it will not be used by Drupal
    if ($container->has('cache_tags.invalidator.checksum.couchbase')) {
      $definition = $container->getDefinition('cache_tags.invalidator.checksum.couchbase');
      $definition->setTags([]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {

    /** @var \Drupal\couchbasedrupal\CouchbaseManager */
    $manager = $container->get('couchbasedrupal.manager');

    $info = [];

    try {
      $bucket = $manager->getBucketFromConfig('default');
      $info = $bucket->manager()->info();
    }
    catch (\Throwable $e) {
      $variables = ['message' => $e->getMessage()];
      $this->logError("Unable to connect to couchbase bucket. Fix any issues and rebuild the service container. {message}", $variables, $e);
      $this->disableRelatedServices($container);
      return;
    }

    // Make sure that we are using a couchbase bucket type. The flood implementation
    // relies on N1QL that does not work for memcache buckets.
    if ($info['bucketType'] !== 'membase') {
      $this->logError("The Drupal/Couchbase integration requires the default bucket to be of type 'couchbase' currently '{$info['bucketType']}'. Update your couchbase configuration and rebuild the service container.");
      $this->disableRelatedServices($container);
      return;
    }

    // Set chained fast backend persitent storage.
    if ($container->has('cache.backend.chainedfast')) {
      $definition  = $container->getDefinition('cache.backend.chainedfast');
      $args = $definition->getArguments();
      // Add the missins optional arguments
      if (!isset($args[1])) {
        $args[] = 'cache.backend.couchbase';
        $definition->setArguments($args);
      }
    }

    // Set chained fast backend persitent storage.
    if ($container->has('cache.backend.superchainedfast')) {
      $definition  = $container->getDefinition('cache.backend.superchainedfast');
      $args = $definition->getArguments();
      // Add the missins optional arguments
      if (!isset($args[1])) {
        $args[] = 'cache.backend.couchbase';
        $definition->setArguments($args);
      }
    }

    // Set chained fast backend persitent storage.
    if ($container->has('cache.rawbackend.chainedfast')) {
      $definition  = $container->getDefinition('cache.rawbackend.chainedfast');
      $args = $definition->getArguments();
      // Add the missins optional arguments
      if (!isset($args[1])) {
        $args[] = 'cache.rawbackend.couchbase';
        $definition->setArguments($args);
      }
    }

    // Override tag checksum invalidator.
    $definition = $container->getDefinition('cache_tags.invalidator.checksum');
    if ($definition->getClass() == DatabaseCacheTagsChecksum::class
      || $definition->getClass() == \Drupal\supercache\Cache\CacheCacheTagsChecksum::class) {
      $couchbase_definition = $container->getDefinition('cache_tags.invalidator.checksum.couchbase');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
    }

    // Override locking backend.
    $definition = $container->getDefinition('lock');
    if ($definition->getClass() == DatabaseLockBackend::class
      || $definition->getClass() == \Drupal\sqlsrv\Lock\DatabaseLockBackend::class) {
      $couchbase_definition = $container->getDefinition('lock.couchbase');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
    }

    // Override persistent locking backend.
    $definition = $container->getDefinition('lock.persistent');
    if ($definition->getClass() == PersistentDatabaseLockBackend::class
      || $definition->getClass() == \Drupal\sqlsrv\Lock\PersistentDatabaseLockBackend::class) {
      $couchbase_definition = $container->getDefinition('lock.persistent.couchbase');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
    }

    // Override the flood service.
    $definition = $container->getDefinition('flood');
    if ($definition->getClass() == \Drupal\Core\Flood\DatabaseBackend::class) {
      $couchbase_definition = $container->getDefinition('flood.couchbase');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
    }

    // Override the cache factory class...
    // TODO: Always override this, even if couchbase is down
    // in order to prevent couchbase backends from being used
    // if the server is down.
    $definition = $container->getDefinition('cache_factory');
    if ($definition->getClass() == \Drupal\Core\Cache\CacheFactory::class) {
      $definition->setClass(\Drupal\couchbasedrupal\Cache\CacheFactory::class);
    }

    $this->deployIndexes($container->get('couchbasedrupal.manager'));
  }

  /**
   * Deploy indexes neede by the diferent components.
   *
   * @param CouchbaseManager $manager
   */
  protected function deployIndexes(\Drupal\couchbasedrupal\CouchbaseManager $manager) {

    foreach ($manager->listServers() as $name) {
      $bucket = $manager->getBucketFromConfig($name);
      $bucket_name = $manager->getClusterDefaultBucketName($name);

      if (!$bucket->indexExists("#primary")) {
        $query = N1qlQuery::fromString("CREATE PRIMARY INDEX ON {$bucket_name} USING GSI");
        $bucket->queryN1QL($query);
      }

      if (!$bucket->indexExists("id_ix")) {
        $query = N1qlQuery::fromString("CREATE INDEX id_ix on {$bucket_name}(meta().id);");
        $bucket->queryN1QL($query);
      }

      $enable_flood_index = $manager->settings['servers'][$bucket->getName()]['enable_flood_index'] ?? FALSE;

      if ($enable_flood_index && !$bucket->indexExists("id_flood_{$manager->getSitePrefix()}")) {
        $index_prefix = "{$manager->getSitePrefix()}_flood_";
        $index_prefix = $bucket->escapePrefix($index_prefix);
        $query = N1qlQuery::fromString("CREATE INDEX id_flood_{$manager->getSitePrefix()} on {$bucket_name}(event, identifier, timestamp) WHERE META({$bucket_name}).id LIKE \"{$index_prefix}%\"");
        $bucket->queryN1QL($query);
      }
    }
  }
}
