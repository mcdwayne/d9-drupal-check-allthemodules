<?php

namespace Drupal\icon\Plugin\Icon;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IconSetBase
 */
abstract class IconSetBase extends PluginBase implements IconSetInterface {

  use ContainerAwareTrait;

  private $icons = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container = NULL, $icons = []) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!isset($container)) {
      $container = \Drupal::getContainer();
    }
    $this->setContainer($container);
    $this->icons = $icons;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return isset($this->pluginDefinition['label']) ? $this->pluginDefinition['label'] : $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons() {
    return isset($this->pluginDefinition['icons']) ? $this->pluginDefinition['icons'] : $this->icons;
  }


  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return isset($this->pluginDefinition['provider']) ? $this->pluginDefinition['provider'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return isset($this->pluginDefinition['url']) ? $this->pluginDefinition['url'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return isset($this->pluginDefinition['version']) ? $this->pluginDefinition['version'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return isset($this->pluginDefinition['path']) ? $this->pluginDefinition['path'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderer() {
    return isset($this->pluginDefinition['renderer']) ? $this->pluginDefinition['renderer'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return isset($this->pluginDefinition['settings']) ? $this->pluginDefinition['settings'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttached() {
    return isset($this->pluginDefinition['attached']) ? $this->pluginDefinition['attached'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function process() {

  }

  /**
   * {@inheritdoc}
   */
  public function getIcon($key = 'icon') {
    if (isset($this->icons[$key])) {
      return $this->icons[$key];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setIcon($value, $key = 'icon') {
    $this->icons[$key] = $value;
  }

}
