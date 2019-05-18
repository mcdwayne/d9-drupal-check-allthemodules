<?php

namespace Drupal\plus_enhancements;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\plus\Plugin\PluginProviderTypeInterface;
use Drupal\plus\ProviderPluginManager;
use Drupal\plus_enhancement\Annotation\Enhancement;
use Drupal\plus_enhancements\Plugin\Enhancement\EnhancementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EnhancementsPluginManager extends ProviderPluginManager {

  /**
   * Constructs a new \Drupal\plus\AlterPluginManager object.
   *
   * @param \Drupal\plus\Plugin\PluginProviderTypeInterface $provider_type
   *   The plugin provider type used for discovery.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The backend cache service to use.
   */
  public function __construct(PluginProviderTypeInterface $provider_type, CacheBackendInterface $cache_backend) {
    parent::__construct($provider_type, 'Plugin/Enhancements', EnhancementInterface::class, Enhancement::class, $cache_backend);
    $this->alterInfo('plus_enhancements_plugin_manager');
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.providers'),
      $container->get('cache.discovery')
    );
  }

}
