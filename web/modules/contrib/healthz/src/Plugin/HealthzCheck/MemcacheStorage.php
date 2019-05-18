<?php

namespace Drupal\healthz\Plugin\HealthzCheck;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\healthz\Plugin\HealthzCheckBase;
use Drupal\memcache_storage\DrupalMemcachedUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a check for memcache_storage module connectivity.
 *
 * @HealthzCheck(
 *   id = "memcache_storage",
 *   title = @Translation("Memcache storage"),
 *   description = @Translation("Checks the connection provided by the memcache_storage module is working.")
 * )
 */
class MemcacheStorage extends HealthzCheckBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SearchApiSolr constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function check() {
    // See memcache_storage_requirements().
    $pecl_extension = DrupalMemcachedUtils::getPeclExtension();
    if (!$pecl_extension) {
      $this->addError($this->t("Memcache PECL extension not configured correctly or does not exist."));
      return FALSE;
    }
    $settings = Settings::get('memcache_storage');
    $server_list = !empty($settings['memcached_servers']) ? $settings['memcached_servers'] : ['127.0.0.1:11211' => 'default'];
    foreach ($server_list as $memcached_server => $cluster_name) {

      // Create a new memcached connection.
      $memcached = new $pecl_extension();

      // Get host & port information for every memcached server.
      list($host, $port) = DrupalMemcachedUtils::parseServerInfo($memcached_server);

      // Add a new server for memcached connection and check if was successfull.
      $connected = FALSE;
      if ($pecl_extension == 'Memcache') {
        $connected = $memcached->connect($host, $port);
      }
      elseif ($pecl_extension == 'Memcached') {
        $memcached->addServer($host, $port);
        $servers_stats = $memcached->getStats();
        $server_stats = array_shift($servers_stats);
        $connected = !empty($server_stats['uptime']);
      }

      if (!$connected) {
        $this->addError($this->t("Unable to connect to memcache cluster '@cluster' (@server)", ['@server' => $memcached_server, '@cluster' => $cluster_name]));
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return $this->moduleHandler->moduleExists('memcache_storage');
  }

}
