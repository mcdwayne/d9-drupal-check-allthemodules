<?php

namespace Drupal\elastic_search\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Field definition plugins.
 */
abstract class ElasticEnabledEntityBase extends PluginBase implements ElasticEnabledEntityInterface {

  /**
   * @inheritdoc
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {

    return new static ($configuration,
                       $plugin_id,
                       $plugin_definition);
  }

}
