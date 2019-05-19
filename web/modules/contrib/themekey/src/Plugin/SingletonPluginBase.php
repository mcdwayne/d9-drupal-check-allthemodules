<?php

/**
 * @file
 * Provides Drupal\themekey\PropertyBase.
 */

namespace Drupal\themekey\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use SebastianBergmann\Exporter\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class SingletonPluginBase extends PluginBase implements ContainerFactoryPluginInterface {

  protected static $instances = array();

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    if (!isset(static::$instances[$plugin_id])) {
      $interfaces = class_implements($plugin_definition['class']);
      foreach ($interfaces as $interface) {
        if (is_subclass_of($interface, '\Drupal\themekey\Plugin\SingletonPluginInspectionInterface')) {
          $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition, $interface);
          static::$instances[$plugin_id] = new $plugin_class($configuration, $plugin_id, $plugin_definition);
          break;
        }
      }
    }

    if (!isset(static::$instances[$plugin_id])) {
      // TODO
      throw new Exception();
    }

    return static::$instances[$plugin_id];
  }
}

