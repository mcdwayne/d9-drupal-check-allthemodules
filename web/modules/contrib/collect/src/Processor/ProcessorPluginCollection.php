<?php
/**
 * @file
 * Contains \Drupal\collect\Processor\ProcessorPluginCollection.
 */

namespace Drupal\collect\Processor;

use Drupal\collect\Model\ModelPluginInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * Plugin collection for processing.
 */
class ProcessorPluginCollection extends DefaultLazyPluginCollection {

  /**
   * The key within the plugin configuration that contains the plugin ID.
   *
   * @var string
   */
  protected $pluginKey = 'plugin_id';

  /**
   * The model plugin instance containing configured processors.
   *
   * @var \Drupal\collect\Model\ModelPluginInterface
   */
  protected $modelPlugin;

  /**
   * Constructs a new ProcessorPluginCollection object.
   */
  public function __construct(PluginManagerInterface $manager, array $configurations = array(), ModelPluginInterface $model_plugin) {
    parent::__construct($manager, $configurations);
    $this->modelPlugin = $model_plugin;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $configuration = isset($this->configurations[$instance_id]) ? $this->configurations[$instance_id] : array();
    if (!isset($configuration[$this->pluginKey])) {
      throw new PluginNotFoundException($instance_id);
    }
    $this->set($instance_id, $this->manager->createInstance($configuration[$this->pluginKey], $configuration, $this->modelPlugin));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configurations = parent::getConfiguration();
    uksort($configurations, [$this, 'sortHelper']);
    return $configurations;
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($a_id, $b_id) {
    return $this->get($a_id)->getWeight() - $this->get($b_id)->getWeight();
  }

}
