<?php

namespace Drupal\plus;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\plus\Annotation\Alter;
use Drupal\plus\Plugin\Alter\AlterInterface;
use Drupal\plus\Plugin\PluginProviderTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manages discovery and instantiation of Bootstrap hook alters.
 *
 * @ingroup plugins_alter
 */
class AlterPluginManager extends ProviderPluginManager {

  /**
   * Constructs a new \Drupal\plus\AlterPluginManager object.
   *
   * @param \Drupal\plus\Plugin\PluginProviderTypeInterface $provider_type
   *   The plugin provider type used for discovery.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The backend cache service to use.
   */
  public function __construct(PluginProviderTypeInterface $provider_type, CacheBackendInterface $cache_backend) {
    parent::__construct($provider_type, 'Plugin/Alter', AlterInterface::class, Alter::class, $cache_backend);
    $this->alterInfo('plus_alter_plugin_manager');
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

  /**
   * @param $type
   * @param $data
   * @param null $context1
   * @param null $context2
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {

    }
  }

}
