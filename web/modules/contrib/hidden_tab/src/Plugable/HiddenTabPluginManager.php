<?php

namespace Drupal\hidden_tab\Plugable;

use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Base class of hidden tab plugin managers with common goodies.
 *
 * Acts as an interface too.
 */
abstract class HiddenTabPluginManager extends DefaultPluginManager {

  /**
   * Id of the plugin.
   *
   * @var string
   */
  protected $pid;

  /**
   * Id of the plugin manager.
   *
   * @return string
   */
  public function id(): string {
    return $this->pid;
  }

  /**
   * all the implementing plugins.
   *
   * @param string|null $tagged
   *   Only return plugins having this tag.
   *
   * @return \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase[]
   *   all the implementing plugins.
   *
   * @see \Drupal\hidden_tab\Plugable\PluginHelper::pluginsSorted()
   */
  public function plugins(?string $tagged = NULL): array {
    // TODO do we have to cache ourselves? or is it already done?
    static $fast;
    if (!isset($fast)) {
      $fast['store'] = &drupal_static(static::class . __function__ . $this->id(), []);
    }
    if (!isset($fast['store'][$this->id()])) {
      // TODO do not instantiate all.
      $type = $this->instance();
      foreach ($type->getDefinitions() as $def) {
        $fast['store'][$this->id()][$def['id']] = $type->createInstance($def['id']);
      }
    }

    $ret = $fast['store'][$this->id()];
    if ($tagged) {
      $ret = array_filter($ret, function (HiddenTabPluginInterfaceBase $plugin) use ($tagged) {
        return empty($plugin->tags()) || in_array($tagged, $plugin->tags());
      });
    }
    return $ret ?: [];
  }

  /**
   * all the implementing plugins, but sorted by weight.
   *
   * @return \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase[]
   *   all the implementing plugins, but sorted by weight.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see \Drupal\hidden_tab\Plugable\PluginHelper::plugins()
   */
  public function pluginsSorted(): array {
    $plugins = $this->plugins();
    usort($plugins, function (HiddenTabPluginBase $a, HiddenTabPluginBase $b) {
      return $a->weight() > $b->weight();
    });
    return $plugins;
  }

  /**
   * load a single plugin by id.
   *
   * @param string $plugin_id
   *   id of the plugin.
   *
   * @return \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase
   *   a single plugin by id.
   */
  public function plugin(string $plugin_id): HiddenTabPluginInterfaceBase {
    $all = $this->plugins();
    $plugin = isset($all[$plugin_id]) ? $all[$plugin_id] : NULL;
    if (!$plugin) {
      throw new \RuntimeException('plugin [' . $this->id() . "] not found: [$plugin_id]");
    }
    return $plugin;
  }

  /**
   * Check and see if a plugin with given Id exists or not.
   *
   * @param string $id
   *   Plugin id to check.
   *
   * @return bool
   *   If plugin exists.
   */
  public function exists(string $id): bool {
    return isset($this->plugins()[$id]);
  }

  /**
   * plugins and their label suitable for a select element options.
   *
   * @param string|null $tagged
   *   Only return plugins having this tag.
   * @param bool $none_option
   *   Add none option to select element.
   *
   * @return array
   *   plugins and their label suitable for a select element options.
   */
  public function pluginsForSelectElement(?string $tagged = NULL, $none_option = FALSE): array {
    $options = [];
    foreach ($this->plugins($tagged) as $plugin) {
      $options[$plugin->id()] = $plugin->label();
    }
    if ($none_option) {
      $options = ['' => t('=None=')] + $options;
    }
    return $options;
  }

  /**
   * Finds label of a plugin.
   *
   * @param string $plugin_id
   *   Plugin id.
   *
   * @return string
   *   Label of plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function labelOfPlugin(string $plugin_id): string {
    if ($this->exists($plugin_id)) {
      return $this->plugin($plugin_id)->label();
    }
    else {
      return t('Missing Plugin: [' . $plugin_id . ']');
    }
  }

  public abstract static function instance(): HiddenTabPluginManager;

}

