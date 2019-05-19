<?php

namespace Drupal\supercache;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

use Drupal\Core\Site\Settings;

use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\Core\Cache\DatabaseCacheTagsChecksum;
use Drupal\Core\Cache\ChainedFastBackendFactory;
use Drupal\Core\KeyValueStore\KeyValueFactory;

use Drupal\supercache\KeyValueStorage\ChainedStorage;

/**
 * Hard overrides some of core services with their couchbase implementation.
 */
class SupercacheServiceProvider implements ServiceProviderInterface {
  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {

    // Override the default chained fast backend.
    $definition = $container->getDefinition('cache.backend.chainedfast');
    if ($definition->getClass() == ChainedFastBackendFactory::class) {
      $couchbase_definition = $container->getDefinition('cache.backend.superchainedfast');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
      $definition->setTags($couchbase_definition->getTags());
    }

    // Override the tag checksum invalidator.
    $definition = $container->getDefinition('cache_tags.invalidator.checksum');
    if ($definition->getClass() == DatabaseCacheTagsChecksum::class) {
      $couchbase_definition = $container->getDefinition('cache_tags.invalidator.checksum.supercache');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
      $definition->setTags($couchbase_definition->getTags());
    }

    // Override the default keyvalue store.
    $param = $container->getParameter('factory.keyvalue');
    if ($param['default'] == 'keyvalue.database') {
      $param['default'] = 'keyvalue.supercache';
      $container->setParameter('factory.keyvalue', $param);
    }

    // Override the default keyvalue expirable store.
    // We use 'default' and 'keyvalue_expirable_default' due
    // to a bug in the core service.
    $param = $container->getParameter('factory.keyvalue.expirable');
    if ((isset($param['default']) && $param['default'] == 'keyvalue.expirable.database') || (isset($param['keyvalue_expirable_default']) && $param['keyvalue_expirable_default'] == 'keyvalue.expirable.database')) {
      $param['default'] = 'keyvalue.expirable.supercache';
      $param['keyvalue_expirable_default'] = 'keyvalue.expirable.supercache';
      $container->setParameter('factory.keyvalue.expirable', $param);
    }

  }
}
