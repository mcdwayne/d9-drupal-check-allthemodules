<?php

namespace Drupal\config_replace;

/**
 * Provides an interface for the ConfigReplacer.
 */
interface ConfigReplacerInterface {

  /**
   * Extension sub-directory containing default configuration for installation.
   */
  public const CONFIG_REWRITE_DIRECTORY = 'config/rewrite';

  /**
   * Rewrites module config.
   *
   * @param $module
   *   The name of a module (without the .module extension).
   */
  public function rewriteModuleConfig($module);

  /**
   * @param array $original_config
   * @param array $rewrite
   * @param string $configName
   * @param string $extensionName
   *
   * @return array
   */
  public function rewriteConfig($original_config, $rewrite, $configName, $extensionName);

}
