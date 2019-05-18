<?php

namespace Drupal\environmental_config;

/**
 * @file
 * Contains EnvironmentDetector.php.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EnvironmentDetector.
 *
 * @package Drupal\environmental_config
 */
class EnvironmentDetector implements ContainerInjectionInterface {

  /**
   * The environment detector manager.
   *
   * @var \Drupal\environmental_config\EnvironmentDetectorManager
   */
  protected $environmentDetectorManager;

  /**
   * EnvironmentDetector constructor.
   *
   * @param \Drupal\environmental_config\EnvironmentDetectorManager $environmentDetectorManager
   *   The environment detector manager.
   */
  public function __construct(EnvironmentDetectorManager $environmentDetectorManager) {
    $this->environmentDetectorManager = $environmentDetectorManager;
  }

  /**
   * ContainerInjectionInterface requirement.
   *
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('plugin.manager.environmental_config.environmentdetectormanager')
    );
  }

  /**
   * Detects the current environment.
   *
   * Queries the plugins of type EnvironmentDetector ordered by weight
   * to find a string environment.
   *
   * @return null|string
   *   The return.
   */
  public function detect() {
    $pluginsByWeight = $this->getPluginsByWeight();

    // Iterating through the ordered plugins.
    foreach ($pluginsByWeight as $plugin) {
      $env = $plugin->getEnvironment();
      if ($env && $this->envFolderIsSet($env, $plugin->getId())) {
        return $env;
      }
    }

    return NULL;
  }

  /**
   * Gets plugins by weight.
   *
   * @return array
   *   The return.
   */
  protected function getPluginsByWeight() {
    $pluginsByWeight = [];
    // Ordering plugins.
    foreach ($this->environmentDetectorManager->getDefinitions() as $pluginName => $pluginValue) {
      $plugin = $this->environmentDetectorManager->createInstance($pluginName);
      $pluginsByWeight[$plugin->getWeight()] = $plugin;
    }

    // Sort by weight.
    ksort($pluginsByWeight);

    return $pluginsByWeight;
  }

  /**
   * Environment folder is set.
   *
   * Checks whether the provided $env is a valid setting
   * by calling config_get_config_directory.
   *
   * @param string $env
   *   The env.
   * @param string $pluginId
   *   The plugin id.
   *
   * @return bool
   *   The return.
   */
  protected function envFolderIsSet($env, $pluginId) {
    try {
      return config_get_config_directory($env);
    }
    catch (\Exception $e) {
      drupal_set_message(t('environmental_config error from plugin %plugin: %message', ['%plugin' => $pluginId, '%message' => $e->getMessage()]), 'error');
    }
    return FALSE;
  }

}
