<?php

/**
 * @file
 * Contains \Drupal\_config\_Config.
 */

namespace Drupal\_config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Yaml;

/**
 * Provides custom configuration management functions.
 */
class _Config {

  /**
   * Check if custom _config file exists.
   *
   * @param string $name
   *   Name of the YAML configuration file in {module_name}/_config.
   *
   * @return bool
   *   TRUE if the config file exists.
   */
  public function exists($name) {
    $names = explode('.', $name);
    $module = array_shift($names);
    if (!\Drupal::moduleHandler()->moduleExists($module)) {
      return FALSE;
    }

    $path = drupal_get_path('module', $module) . '/_config/' . $name . '.yml';
    return file_exists($path);
  }

  /**
   * Get custom config for a custom module.
   *
   * Basically, this function allows a custom module to store
   * configuration information on disk via a YAML file within a custom module's
   * '_config' directory.
   *
   * @param string $name
   *   Name of the YAML configuration file in {module_name}/_config.
   * @param null|string $key
   *   A string that maps to a key within the configuration data.
   *
   * @return mixed
   *   The data that was requested.
   *
   * @throws \Exception
   *   If configuration file does not exist.
   *
   * @see \Drupal\Core\Config\ConfigBase::get
   */
  public function get($name = NULL, $key = NULL) {
    $config = &drupal_static(__FUNCTION__, []);

    $names = explode('.', $name);
    $module = array_shift($names);

    $path = drupal_get_path('module', $module) . '/_config/' . $name . '.yml';
    if (!isset($config[$path])) {
      if (!file_exists($path)) {
        $args = ['@name' => $name, '@module' => $module, '@path' => $path];
        throw new \Exception(new FormattableMarkup("Config '@name' for '@module' does not exist. (@path)", $args));
      }
      $config[$path] = Yaml::decode(file_get_contents($path));
    }

    // Copied from: \Drupal\Core\Config\Config::get()
    if (empty($key)) {
      return $config[$path];
    }
    else {

      // Look for simple key, which can even include dot delimiter.
      // This allows _config YAML to easily reference routes.
      if (isset($config[$path][$key])) {
        return $config[$path][$key];
      }

      $parts = explode('.', $key);
      if (count($parts) == 1) {
        return isset($config[$path][$key]) ? $config[$path][$key] : NULL;
      }
      else {
        $key_exists = NULL;
        $values = $config[$path];
        $value = NestedArray::getValue($values, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      }

    }
  }

}
