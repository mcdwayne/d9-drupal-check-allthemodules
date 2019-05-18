<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\access_filter\Plugin\ConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all condition plugins.
 */
abstract class ConditionBase extends PluginBase implements ConditionInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isNegated() {
    return !empty($this->configuration['negate']);
  }

}
