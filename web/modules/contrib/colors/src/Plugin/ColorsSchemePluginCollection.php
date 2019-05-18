<?php

namespace Drupal\colors\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Component\Plugin\PluginManagerInterface;

class ColorsSchemePluginCollection extends DefaultLazyPluginCollection{

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructs a ColorsSchemePluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   */
  public function __construct(PluginManagerInterface $manager) {
    $this->manager = $manager;

    // Store all display IDs to access them easy and fast.
    $instance_ids = array_keys($this->manager->getDefinitions());
    $this->instanceIDs = array_combine($instance_ids, $instance_ids);

    parent::__construct($manager, $this->instanceIDs);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($plugin_id) {
    if (isset($this->pluginInstances[$plugin_id])) {
      return;
    }

    $this->pluginInstances[$plugin_id] = $this->manager->createInstance($plugin_id, array());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration($configuration) {
    return $this;
  }



}
