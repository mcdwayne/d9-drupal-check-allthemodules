<?php

namespace Drupal\layout_config_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 */
class LayoutConfigBlockDerivative extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Stores the config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($base_plugin_id, ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->configFactory->listAll("layout_config_block.layout_block") as $config_id) {
      // We are assuming that this is a Drupal\Core\Config\Config Object
      // (there does not appear to be an inteface to assert).
      $config = $this->configFactory->get($config_id);
      // Always add back the base definition.
      $this->derivatives[$config_id] = $base_plugin_definition;
      // Our config has a label lets use it.
      $this->derivatives[$config_id]['admin_label'] = $config->get("label");
      // Add the config object to the definition.
      $this->derivatives[$config_id]['config'] = $config;
    }
    return $this->derivatives;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('config.factory')
    );
  }

}
