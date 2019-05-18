<?php

namespace Drupal\preprocess;

/**
 * Manages preprocessing.
 *
 * @package Drupal\preprocess
 */
class PreprocessManager implements PreprocessManagerInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\preprocess\PreprocessPluginManagerInterface
   */
  private $pluginManager;

  /**
   * Constructs a PreprocessManager object.
   *
   * @param \Drupal\preprocess\PreprocessPluginManagerInterface $plugin_manager
   *   The plugin manager.
   */
  public function __construct(PreprocessPluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(string $hook, array $variables): array {
    foreach ($this->pluginManager->getPreprocessors($hook) as $preprocessor) {
      $variables = $preprocessor->preprocess($variables);
    }

    return $variables;
  }

}
