<?php

namespace Drupal\visualn\Core;

interface VisualNPluginJsInterface {

  /**
   * Modify plugin configuration before attaching to js settings.
   *
   * Can be used to translate strings etc.
   *
   * @param array $plugin_config
   */
  public function prepareJsConfig(array &$plugin_config);

  /**
   * Get plugin jsId.
   * Plugin jsId is used in plugin (drawer, mapper, adapter) js script to identify its function object.
   *
   * @return string $js_id
   */
  public function jsId();

}
