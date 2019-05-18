<?php

namespace Drupal\inmail\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\inmail\InmailPluginConfigInterface;

/**
 * Defines a config entity skeleton for plugin configuration.
 */
abstract class PluginConfigEntity extends ConfigEntityBase implements InmailPluginConfigInterface {

  /**
   * The machine name of the plugin configuration.
   *
   * @var string
   */
  protected $id;

  /**
   * The translatable, human-readable name of the plugin configuration.
   *
   * @var string
   */
  protected $label;

  /**
   * The ID of the plugin for this configuration.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The configuration for the plugin.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The plugin instance.
   *
   * @var \Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerInterface|\Drupal\inmail\Plugin\inmail\Deliverer\DelivererInterface|\Drupal\inmail\Plugin\inmail\Handler\HandlerInterface
   */
  protected $pluginInstance;

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerInterface|\Drupal\inmail\Plugin\inmail\Deliverer\DelivererInterface|\Drupal\inmail\Plugin\inmail\Handler\HandlerInterface The instantiated plugin.
   * The instantiated plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Throws an exception in case of missing plugin.
   */
  public function getPluginInstance() {
    if (empty($this->pluginInstance)) {
      $this->pluginInstance = \Drupal::service('plugin.manager.inmail.' . $this->pluginType)->createInstance($this->plugin, $this->configuration);
    }

    return $this->pluginInstance;
  }

  /**
   * Returns the plugin type.
   *
   * @return string
   *   The plugin type.
   */
  public function getPluginType() {
    return $this->pluginType;
  }

  /**
   * Flag determining whether a plugin is available to be used in processing.
   *
   * @return bool
   *   TRUE if the plugin is available. Otherwise, FALSE.
   */
  public function isAvailable() {
    $is_available = FALSE;
    if ($plugin = $this->getPluginInstance()) {
      $is_available = $plugin->isAvailable();
    }

    return $is_available;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin) {
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration ) {
    $this->configuration = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

}
