<?php

namespace Drupal\adva\Plugin\adva\Manager;

use Drupal\adva\Plugin\adva\AccessConsumerInterface;

use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Implements custom factory for AccessProvider Plugins.
 *
 * Extends default plugin creation to provide the AccessConsumer connected to
 * the AccessProvider.
 */
class AccessProviderFactory extends ContainerFactory {

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = [], AccessConsumerInterface $consumer = NULL) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition, $this->interface);

    $provider_class = 'Drupal\adva\Plugin\adva\AccessProviderInterface';

    if ($consumer !== NULL && is_subclass_of($plugin_class, $provider_class)) {
      if (method_exists($plugin_class, "create")) {
        return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition, $consumer);
      }
      else {
        return new $plugin_class($configuration, $plugin_id, $plugin_definition, $consumer);
      }
    }

    $instance = parent::createInstance($plugin_id, $configuration);

    if ($consumer !== NULL && method_exists($instance, "setConsumer")) {
      $instance->setConsumer($consumer);
    }

    return $instance;

  }

}
