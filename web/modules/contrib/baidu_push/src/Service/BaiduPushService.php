<?php

namespace Drupal\baidu_push\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;

/**
 * The BaiduPushService.
 *
 * Provides helper methods for the Baidu push module.
 */
class BaiduPushService implements BaiduPushServiceInterface {

  /**
   * The Baidu push module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * The auto push conditions collection.
   *
   * @var \Drupal\Core\Condition\ConditionInterface[]
   */
  protected $autoPushConditions;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ExecutableManagerInterface $condition_plugin_manager
  ) {
    $this->config = $config_factory->get('baidu_push.settings');
    $this->conditionPluginManager = $condition_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionPluginManager() {
    return $this->conditionPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function isAutoPushEnabled() {
    return !empty($this->config->get('enable_auto_push'));
  }

  /**
   * {@inheritdoc}
   */
  public function getAutoPushConditions() {
    if (!isset($this->autoPushConditions)) {
      $defaults = [
        'request_path' => [],
        'language' => [],
      ];

      $settings = $this->config->get('auto_push_conditions');
      $settings = empty($settings) ? $defaults : array_merge_recursive($defaults, $settings);

      $condition_plugins = [];
      foreach ($settings as $instance_id => $config) {
        $condition_plugins[$instance_id] = $this->conditionPluginManager->createInstance($instance_id, $config);
      }

      $this->autoPushConditions = $condition_plugins;
    }
    return $this->autoPushConditions;
  }

  /**
   * {@inheritdoc}
   */
  public function getAutoPushCondition($instance_id) {
    return $this->getAutoPushConditions()[$instance_id];
  }

}
