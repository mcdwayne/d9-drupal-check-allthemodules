<?php

namespace Drupal\cloud\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\cloud\Entity\CloudServerTemplateInterface;

/**
 * Provides the default cloud_server_template_plugin manager.
 */
class CloudServerTemplatePluginManager extends DefaultPluginManager implements CloudServerTemplatePluginManagerInterface {

  /**
   * Provides default values for all cloud_server_template_plugin plugins.
   *
   * @var array
   */
  protected $defaults = [
    'id' => 'cloud_server_template',
    'entity_type' => 'cloud_server_template',
  ];

  /**
   * Constructs a new CloudServerTemplatePluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    // Add more services as required.
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'cloud_server_template_plugin', ['cloud_server_template_plugin']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('cloud.server.template.plugin', $this->moduleHandler->getModuleDirectories());
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
      throw new PluginException(sprintf('CloudServerTemplatePlugin plugin property (%s) definition "is" is required.', $plugin_id));
    }

    if (empty($definition['entity_bundle'])) {
      throw new PluginException(sprintf('entity_bundle property is required for (%s)', $plugin_id));
    }

    if (empty($definition['cloud_context'])) {
      throw new PluginException(sprintf('cloud_context property is required for (%s)', $plugin_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadPluginVariant($cloud_context) {
    $plugin = FALSE;
    foreach ($this->getDefinitions() as $key => $definition) {
      if ($definition['cloud_context'] == $cloud_context) {
        $plugin = $this->createInstance($key);
        break;
      }
    }
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function launch(CloudServerTemplateInterface $cloud_server_template, FormStateInterface $form_state = NULL) {
    $plugin = $this->loadPluginVariant($cloud_server_template->getCloudContext());
    return $plugin->launch($cloud_server_template, $form_state);
  }

}
