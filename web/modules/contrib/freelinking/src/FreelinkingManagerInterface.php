<?php

namespace Drupal\freelinking;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\freelinking\Plugin\FreelinkingPluginInterface;

/**
 * Freelinking plugin manager interface.
 */
interface FreelinkingManagerInterface extends PluginManagerInterface {

  /**
   * Initialize method.
   *
   * @param \Traversable $namespaces
   *   An array of namespaces.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler for alter functionality.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager);

  /**
   * Get the plugin to use based on the indicator and a list of allowed plugins.
   *
   * @param string $indicator
   *   The indicator string to test.
   * @param array $allowed_plugins
   *   An indexed array of plugin names.
   * @param array $options
   *   The settings from the filter. This is an associative array.
   *
   * @return \Drupal\freelinking\Plugin\FreelinkingPluginInterface|false
   *   The plugin to use or FALSE if not found.
   *
   * @see \Drupal\freelinking\Plugin\Filter\Freelinking::process()
   */
  public function getPluginFromIndicator($indicator, array $allowed_plugins = [], array $options = []);

  /**
   * Build link structure for a plugin with target parameters.
   *
   * @param \Drupal\freelinking\Plugin\FreelinkingPluginInterface $plugin
   *   The freelinking plugin.
   * @param array $target
   *   The target array. @see ::parseTarget().
   *
   * @return mixed
   *   Either an array or a string.
   */
  public function buildLink(FreelinkingPluginInterface $plugin, array $target);

  /**
   * Parse link arguments from the target string.
   *
   * @param string $target
   *   The target string to parse arguments for the link.
   * @param string $langcode
   *   The language code i.e. "en".
   *
   * @return array
   *   Target arguments.
   */
  public function parseTarget($target, $langcode);

  /**
   * Create the error element when plugin not found.
   *
   * @param string $indicator
   *   The name of the failed indicator.
   *
   * @return array
   *   The render array to render the error element.
   */
  public function createErrorElement($indicator);

  /**
   * Create the render array for the respective Freelinking plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param string $target
   *   The target string to parse.
   * @param string $indicator
   *   The indicator string.
   * @param string $langcode
   *   The language code.
   * @param string $plugin_settings_string
   *   The plugin settings serialized as a string.
   * @param string $failover_settings_string
   *   The failover settings serialized as a string. Defaults to an empty array.
   *
   * @return array
   *   A render element.
   *
   * @todo Set cache contexts from plugin.
   * @todo Multilingual support.
   */
  public function createFreelinkElement($plugin_id, $target, $indicator, $langcode, $plugin_settings_string, $failover_settings_string);

}
