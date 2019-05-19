<?php

namespace Drupal\sir_trevor\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

class SirTrevorPluginManager implements SirTrevorPluginManagerInterface {

  /** @var \Drupal\Core\Plugin\Discovery\YamlDiscovery */
  private $discovery;
  /** @var \Drupal\Core\Config\ConfigFactoryInterface */
  private $configFactory;

  public function __construct(ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $configFactory) {
    $this->discovery = new YamlDiscovery('sir_trevor', $moduleHandler->getModuleDirectories());
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->discovery->getDefinition($plugin_id, $exception_on_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->discovery->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefinition($plugin_id) {
    return $this->discovery->hasDefinition($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    return $this->getInstance($this->discovery->getDefinition($plugin_id));
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    if (!empty($options['mixin']) && $options['mixin']) {
      return new SirTrevorMixin($options);
    }
    else {
      return new SirTrevorBlock($options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances() {
    $instances = [];

    foreach ($this->getDefinitions() as $definition) {
      $instances[] = $this->getInstance($definition);
    }

    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocks() {
    $blocks = [];

    foreach ($this->createInstances() as $instance) {
      if ($instance->getType() == SirTrevorPlugin::block) {
        $blocks[] = $instance;
      }
    }

    return $blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledBlocks() {
    $config = $this->configFactory->get('sir_trevor.global');

    $enabled = $config->get('enabled_blocks');
    $allBlocks = $this->getBlocks();
    if (empty($enabled)) {
      return $allBlocks;
    }

    $enabledBlocks = [];
    foreach ($allBlocks as $block) {
      if (in_array($block->getMachineName(), $enabled)) {
        $enabledBlocks[] = $block;
      }
    }
    return $enabledBlocks;
  }
}
