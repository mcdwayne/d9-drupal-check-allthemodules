<?php

namespace Drupal\permutive;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\permutive\Plugin\PermutiveData;

/**
 * Class PermutiveBuilder.
 *
 * @package Drupal\permutive
 */
class PermutiveBuilder implements PermutiveBuilderInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PermutiveBuilder constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config manager.
   */
  public function __construct(PluginManagerInterface $manager, ConfigFactoryInterface $config_factory) {
    $this->manager = $manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiKey() {
    return $this->configFactory->get('permutive.settings')->get('api_key');
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectId() {
    return $this->configFactory->get('permutive.settings')->get('project_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getScriptUrl() {
    return 'https://cdn.permutive.com/' . $this->getProjectId() . '-web.js';
  }

  /**
   * {@inheritdoc}
   */
  public function buildTag() {
    $plugins = $this->getPlugins();

    $tags = [];
    foreach ($plugins as $type => $data_set) {
      foreach ($data_set as $client_type => $plugins) {
        // Pass data for each tag to the plugins to alter.
        $data = new PermutiveData();
        $data->setClientType($client_type);
        foreach ($plugins as $plugin) {
          $plugin->alterData($data);
        }
        $tags[$type][$data->getClientType()] = $data;
      }
    }

    $js = '';
    foreach ($tags as $type => $data_set) {
      foreach ($data_set as $client_type => $data) {
        $js .= 'permutive.' . $type . '("' . $client_type . '", ';
        $js .= json_encode($data->getArray());
        $js .= ');';
      }
    }

    return $js;
  }

  /**
   * Gets the plugins ordered by priority.
   *
   * @return \Drupal\permutive\Plugin\PermutiveInterface[]
   *   An array of Permutive plugins keyed by type & client type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getPlugins() {
    $plugins = [];
    $plugin_definitions = $this->manager->getDefinitions();

    // Order the plugins.
    $unordered = [];
    foreach ($plugin_definitions as $id => $definition) {
      $unordered[$definition['priority']][][$id] = $definition;
    }
    $ordered = [];
    foreach ($unordered as $array) {
      foreach ($array as $plugin_definitions) {
        foreach ($plugin_definitions as $id => $definition) {
          $ordered[$id] = $definition;
        }
      }
    }

    foreach ($ordered as $id => $definition) {
      /** @var \Drupal\permutive\Plugin\PermutiveInterface $plugin */
      $plugin = $this->manager->createInstance($id);
      // Group the plugins by type & data id.
      $plugins[$plugin->getType()][$plugin->getClientType()][] = $plugin;
    }

    return $plugins;
  }

}
