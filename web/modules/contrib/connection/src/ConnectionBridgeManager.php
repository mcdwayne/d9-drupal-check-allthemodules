<?php

namespace Drupal\connection;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Provides the default connection_bridge manager.
 */
class ConnectionBridgeManager extends DefaultPluginManager {

  /**
   * Provides default values for all connection_bridge plugins.
   *
   * @var array
   */
  protected $defaults = [
    'type' => 'drupal_connection',
    'label' => '',
    'base_url' => '',
    'endpoint' => ''
  ];

  /**
   * Constructs a new ConnectionBridgeManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'connection_bridge', ['connection_bridge']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('connections', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    // Ensure each connection definition has a label.
    if (empty($definition['label'])) {
      throw new PluginException(sprintf('Plugin (%s) is missing a required property.', $plugin_id));
    }
    // When base_url is not defined, use site uri as the base_url.
    if (empty($definition['base_url'])) {
      $definition['base_url'] = \Drupal::request()->getSchemeAndHttpHost();
    }
  }

}
