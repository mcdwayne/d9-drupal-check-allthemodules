<?php

namespace Drupal\jsld\Plugin\jsld;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base plugin for more specific plugin bases.
 */
abstract class JsldPluginBase extends PluginBase implements JsldPluginInterface, ContainerFactoryPluginInterface {

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
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    if (isset($this->pluginDefinition['enabled'])) {
      return $this->pluginDefinition['enabled'];
    }
    else {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

}
