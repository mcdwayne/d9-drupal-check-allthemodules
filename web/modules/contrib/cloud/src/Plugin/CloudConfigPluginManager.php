<?php

namespace Drupal\cloud\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Provides the default cloud_config_plugin manager.
 */
class CloudConfigPluginManager extends DefaultPluginManager implements CloudConfigPluginManagerInterface {

  /**
   * Provides default values for all cloud_config_plugin plugins.
   *
   * @var array
   */
  protected $defaults = [
    'id' => 'cloud_config',
    'entity_type' => 'cloud_config',
  ];

  /**
   * The cloud context.
   *
   * @var string
   */
  private $cloudContext;

  /**
   * The cloud config plugin.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginInterface
   */
  private $plugin;

  /**
   * Constructs a new CloudConfigPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    // Add more services as required.
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'cloud_config_plugin', ['cloud_config_plugin']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('cloud.config.plugin', $this->moduleHandler->getModuleDirectories());
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

    if (empty($definition['id'])) {
      throw new PluginException(sprintf('Example plugin property (%s) definition "is" is required.', $plugin_id));
    }

    if (empty($definition['entity_bundle'])) {
      throw new PluginException(sprintf('entity_bundle property is required for (%s)', $plugin_id));
    }

    if (!isset($definition['base_plugin']) && empty($definition['cloud_context'])) {
      throw new PluginException(sprintf('cloud_context property is required for (%s)', $plugin_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setCloudContext($cloud_context) {
    $this->cloudContext = $cloud_context;
    // Load the plugin variant since we know the cloud_context.
    $this->plugin = $this->loadPluginVariant();
    if ($this->plugin == FALSE) {
      throw new CloudConfigPluginException(sprintf('Cannot load cloud config plugin for %s', $this->cloudContext));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadPluginVariant() {
    $plugin = FALSE;
    foreach ($this->getDefinitions() as $key => $definition) {
      if (isset($definition['cloud_context']) && $definition['cloud_context'] == $this->cloudContext) {
        $plugin = $this->createInstance($key);
        break;
      }
    }
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function loadConfigEntity() {
    $config_entity = $this->plugin->loadConfigEntity($this->cloudContext);
    if ($config_entity == FALSE) {
      throw new CloudConfigPluginException(sprintf('Cannot load configuration entity for %s', $this->cloudContext));
    }
    return $config_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function loadConfigEntities($entity_bundle) {
    /* @var \Drupal\cloud\Plugin\CloudConfigPluginInterface $plugin */
    $plugin = $this->loadBasePluginDefinition($entity_bundle);
    if ($plugin == FALSE) {
      throw new CloudConfigPluginException(sprintf('Cannot load Cloud Config Entity for %s', $entity_bundle));
    }
    return $plugin->loadConfigEntities();
  }

  /**
   * Helper method to load the base plugin definition.
   *
   * Useful when there is no cloud_context.
   *
   * @param string $entity_bundle
   *   The entity bundle.
   *
   * @return bool|object
   *   The base plugin definition.
   */
  private function loadBasePluginDefinition($entity_bundle) {
    $plugin = FALSE;
    foreach ($this->getDefinitions() as $key => $definition) {
      if (isset($definition['base_plugin']) && $definition['entity_bundle'] == $entity_bundle) {
        $plugin = $this->createInstance($key);
        break;
      }
    }
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function loadCredentials() {
    return $this->plugin->loadCredentials($this->cloudContext);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceCollectionTemplateName() {
    return $this->plugin->getInstanceCollectionTemplateName();
  }

  /**
   * {@inheritdoc}
   */
  public function getPricingPageRoute() {
    return $this->plugin->getPricingPageRoute();
  }

  /**
   * {@inheritdoc}
   */
  public function getServerTemplateCollectionName() {
    return 'entity.cloud_server_template.collection';
  }

}
