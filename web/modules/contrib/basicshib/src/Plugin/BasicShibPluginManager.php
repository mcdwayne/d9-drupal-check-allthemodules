<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/15/17
 * Time: 9:22 AM
 */

namespace Drupal\basicshib\Plugin;

use Drupal\basicshib\Annotation\BasicShibAuthenticationPlugin;
use Drupal\basicshib\Annotation\BasicShibAuthFilter;
use Drupal\basicshib\Annotation\BasicShibUserProvider;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class BasicShibPluginManager extends DefaultPluginManager {
  public function __construct(
      $type,
      \Traversable $namespaces,
      CacheBackendInterface $cache_backend,
      ModuleHandlerInterface $module_handler
    ) {

    $type_annotations = [
      'user_provider' => BasicShibUserProvider::class,
      'auth_filter' => BasicShibAuthFilter::class,
    ];

    $type_plugins = [
      'user_provider' => UserProviderPluginInterface::class,
      'auth_filter' => AuthFilterPluginInterface::class,
    ];

    parent::__construct(
      'Plugin/basicshib/' . $type,
      $namespaces, $module_handler,
      $type_plugins[$type],
      $type_annotations[$type]
    );
    $this->setCacheBackend($cache_backend, 'basicshib_' . $type . '_plugins');
  }
}
