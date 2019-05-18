<?php

namespace Drupal\developer_suite;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HookBuilder.
 *
 * @package Drupal\developer_suite\Hook
 */
class HookBuilder implements HookBuilderInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private $container;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerFactory;

  /**
   * The plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container, LoggerChannelFactoryInterface $loggerFactory, PluginManagerInterface $pluginManager) {
    $this->container = $container;
    $this->loggerFactory = $loggerFactory;
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function build($hookId) {
    $hookPluginDefinition = $this->getHookDefinition($hookId);

    // If a valid plugin was returned, create a new instance of the plugin
    // class and return it.
    if ($hookPluginDefinition) {
      $hookClass = $hookPluginDefinition['class'];

      // If the hook class implements the ContainerInjectionInterface create an
      // instance via the create() method and pass the service container.
      if (in_array(ContainerInjectionInterface::class, class_implements($hookClass))) {
        return $hookClass::create($this->container);
      }

      return new $hookClass();
    }

    return FALSE;
  }

  /**
   * Returns the plugin hook definition from the plugin manager.
   *
   * @param string $hookId
   *   The hook ID.
   *
   * @return bool|array
   *   The hook definition.
   */
  private function getHookDefinition($hookId) {
    $hookPluginDefinitions = $this->pluginManager->getDefinitions();

    if (isset($hookPluginDefinitions[$hookId])) {
      return $hookPluginDefinitions[$hookId];
    }

    return FALSE;
  }

}
