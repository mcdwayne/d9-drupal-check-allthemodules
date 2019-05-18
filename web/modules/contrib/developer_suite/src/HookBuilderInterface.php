<?php

namespace Drupal\developer_suite;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface HookBuilder.
 *
 * @package Drupal\developer_suite
 */
interface HookBuilderInterface {

  /**
   * HookBuilder constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager.
   */
  public function __construct(ContainerInterface $container, LoggerChannelFactoryInterface $loggerFactory, PluginManagerInterface $pluginManager);

  /**
   * Builds a hook class.
   *
   * @param string $hookId
   *   The hook ID.
   *
   * @return bool|\Drupal\developer_suite\Hook\HookInterface
   *   The hook class or FALSE.
   */
  public function build($hookId);

}
