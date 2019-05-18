<?php

namespace Drupal\baidu_push\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;

/**
 * Baidu Push Service Interface.
 */
interface BaiduPushServiceInterface {

  /**
   * Constructs a BaiduPushService instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_plugin_manager
   *   The factory for condition plugins.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ExecutableManagerInterface $condition_plugin_manager
  );

  /**
   * Return the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   */
  public function getConditionPluginManager();

  /**
   * Whether the auto push function is enabled.
   *
   * @return bool
   *   TRUE, if auto push is enabled, FALSE otherwise.
   */
  public function isAutoPushEnabled();

  /**
   * Return auto push conditions settings.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]
   *   Auto push conditions settings.
   */
  public function getAutoPushConditions();

  /**
   * Gets an auto push condition plugin instance.
   *
   * @param string $instance_id
   *   The condition plugin instance ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   A condition plugin.
   */
  public function getAutoPushCondition($instance_id);

}
