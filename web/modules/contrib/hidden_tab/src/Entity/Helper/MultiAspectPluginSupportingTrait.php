<?php

namespace Drupal\hidden_tab\Entity\Helper;

/**
 * Implements MultiPluginSupportingEntityInterface.
 *
 * @see \Drupal\hidden_tab\Entity\Base\MultiAspectPluginSupportingInterface
 */
trait MultiAspectPluginSupportingTrait {

  /**
   * @var string|null
   *   Plugins data
   */
  protected $plugins;

  private function decode(): array {
    $v = $this->plugins;
    if ($v === NULL) {
      return [];
    }
    if (is_string($v)) {
      $dec = json_decode($v, TRUE);
      if ($dec === NULL) {
        throw new \LogicException('can not decode plugin configuration');
      }
      return $dec;
    }
    if (!is_array($v)) {
      throw new \LogicException('can not decode plugin configuration');
    }
    return $v;
  }

  private function encode(array $value) {
    $v = json_encode($value);
    $this->plugins = $v;
    $this->set('plugins', $v);
  }

  /**
   * See delPlugin() in MultiPluginSupportingEntityInterface.
   *
   * @param string $type
   *   See delPlugin() in MultiPluginSupportingEntityInterface.
   * @param string $plugin
   *   See delPlugin() in MultiPluginSupportingEntityInterface.
   *
   * @return mixed
   *   This
   *
   * @see \Drupal\hidden_tab\Entity\Base\MultiAspectPluginSupportingInterface::delPlugin()
   */
  public function delPlugin(string $type, string $plugin) {
    $c = $this->decode();
    unset($c[$type][$plugin]);
    $this->encode($c);
    return $this;
  }

  /**
   * See pluginConfiguration() in MultiPluginSupportingEntityInterface.
   *
   * @param string $type
   *   See pluginConfiguration() in MultiPluginSupportingEntityInterface.
   * @param string $plugin_id
   *   See pluginConfiguration() in MultiPluginSupportingEntityInterface.
   *
   * @return mixed
   *   See pluginConfiguration() in MultiPluginSupportingEntityInterface.
   *
   * @see \Drupal\hidden_tab\Entity\Base\MultiAspectPluginSupportingInterface::pluginConfiguration()
   */
  public function pluginConfiguration(string $type, string $plugin_id) {
    $c = $this->decode();
    return isset($c[$type][$plugin_id]) ? $c[$type][$plugin_id] : NULL;
  }

  /**
   * See setPluginConfiguration() in MultiPluginSupportingEntityInterface.
   *
   * @param string $type
   *   See pluginConfigurations() in MultiPluginSupportingEntityInterface.
   * @param string $plugin_id
   *   See setPluginConfiguration() in MultiPluginSupportingEntityInterface.
   * @param $configuration
   *   See setPluginConfiguration() in MultiPluginSupportingEntityInterface.
   *
   * @return mixed
   *   This.
   *
   * @see \Drupal\hidden_tab\Entity\Base\MultiAspectPluginSupportingInterface::setPluginConfiguration()
   */
  public function setPluginConfiguration(string $type, string $plugin_id, $configuration) {
    $c = $this->decode();
    $c[$type][$plugin_id] = $configuration;
    $this->encode($c);
    return $this;
  }

  /**
   * See pluginConfigurations() in MultiPluginSupportingEntityInterface.
   *
   * @param string $type
   *   See pluginConfigurations() in MultiPluginSupportingEntityInterface.
   *
   * @return array
   *   All configurations.
   *
   * @see \Drupal\hidden_tab\Entity\Base\MultiAspectPluginSupportingInterface::pluginConfigurations()
   */
  public function pluginConfigurations(?string $type): array {
    $c = $this->decode();
    if (!$type) {
      return $c;
    }
    return isset($c) ? $c : [];
  }

  /**
   * See resetPluginConfigurations() in MultiPluginSupportingEntityInterface.
   *
   * @param string $type
   *   See resetPluginConfigurations() in MultiPluginSupportingEntityInterface.
   *
   * @return mixed
   *   This.
   *
   * @see \Drupal\hidden_tab\Entity\Base\MultiAspectPluginSupportingInterface::resetPluginConfigurations()
   */
  public function resetPluginConfigurations(?string $type) {
    if ($type) {
      $value = $this->decode();
      unset($value[$type]);
      $this->encode($value);
    }
    else {
      $this->encode([]);
    }
    return $this;
  }

}
