<?php

namespace Drupal\core_extend\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting plugin definition IDs to full objects.
 *
 * This is useful in cases where the dynamic elements of the path can't be
 * auto-determined; for example, if your path refers to multiple of the same
 * type of plugin ("example/{plugin1}/foo/{plugin2}") or if the path can act on
 * any plugin type ("example/{plugin_type_1}/{plugin_type_2}/foo").
 *
 * In order to use it you should specify some additional options in your route:
 * @code
 * example.route:
 *   path: foo/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: plugin:plugin_type_1
 * @endcode
 *
 * If you want to have the plugin type itself dynamic in the url you can
 * specify it like the following:
 * @code
 * example.route:
 *   path: foo/{plugin_type_1}/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: plugin:{plugin_type_1}
 * @endcode
 */
class PluginConverter implements ParamConverterInterface {

  /**
   * An array of loaded plugin managers.
   *
   * @var array
   */
  protected $pluginManagers = [];

  /**
   * The plugin service id.
   *
   * @param string $plugin_type_id
   *   The plugin type id, mapped to its service.
   *
   * @return \Drupal\Component\Plugin\CategorizingPluginManagerInterface|null
   *   The plugin manager.
   */
  protected function getpluginManager($plugin_type_id) {
    if (!array_key_exists($plugin_type_id, $this->pluginManagers)) {
      $plugin_service_id = 'plugin.manager.' . $plugin_type_id;
      if (\Drupal::hasService($plugin_service_id)) {
        $this->pluginManagers[$plugin_type_id] = \Drupal::service($plugin_service_id);
      }
      else {
        $this->pluginManagers[$plugin_type_id] = NULL;
      }
    }
    return $this->pluginManagers[$plugin_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $plugin_type_id = $this->getPluginTypeFromDefaults($definition, $name, $defaults);
    if ($manager = $this->getpluginManager($plugin_type_id)) {
      return $manager->getDefinition($value, FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && strpos($definition['type'], 'plugin:') === 0) {
      $plugin_type_id = substr($definition['type'], strlen('plugin:'));
      if (strpos($definition['type'], '{') !== FALSE) {
        $plugin_type_slug = substr($plugin_type_id, 1, -1);
        return $name != $plugin_type_slug && in_array($plugin_type_slug, $route->compile()->getVariables(), TRUE);
      }
      if ($this->getpluginManager($plugin_type_id)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determines the plugin type ID given a route definition and route defaults.
   *
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string
   *   The plugin type ID.
   *
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   *   Thrown when the dynamic plugin type is not found in the route defaults.
   */
  protected function getPluginTypeFromDefaults($definition, $name, array $defaults) {
    $plugin_type_id = substr($definition['type'], strlen('plugin:'));

    // If the plugin type is dynamic, it will be pulled from the route defaults.
    if (strpos($plugin_type_id, '{') === 0) {
      $plugin_type_slug = substr($plugin_type_id, 1, -1);
      if (!isset($defaults[$plugin_type_slug])) {
        throw new ParamNotConvertedException(sprintf('The "%s" parameter was not converted because the "%s" parameter is missing', $name, $plugin_type_slug));
      }
      $plugin_type_id = $defaults[$plugin_type_slug];
    }
    return $plugin_type_id;
  }

}
