<?php

namespace Drupal\hidden_tab\Plugable\MailDiscovery;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabMailDiscoveryAnon;
use Drupal\hidden_tab\Plugable\HiddenTabPluginManager;

/**
 * HiddenTabMailDiscovery plugin manager.
 *
 * @see \Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryInterface
 */
class HiddenTabMailDiscoveryPluginManager extends HiddenTabPluginManager {

  public $pid = HiddenTabMailDiscoveryInterface::PID;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/HiddenTabMailDiscovery',
      $namespaces,
      $module_handler,
      HiddenTabMailDiscoveryInterface::class,
      HiddenTabMailDiscoveryAnon::class
    );
    $this->alterInfo('hidden_tab_mail_discovery_info');
    $this->setCacheBackend($cache_backend, 'hidden_tab_mail_discovery_plugin');
  }

  /**
   * Facory method, create an instance from container.
   *
   * @return \Drupal\hidden_tab\Plugable\HiddenTabPluginManager
   */
  public static function instance(): HiddenTabPluginManager {
    return \Drupal::service('plugin.manager.' . HiddenTabMailDiscoveryInterface::PID);
  }

}
