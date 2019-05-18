<?php

namespace Drupal\analog_digital_clock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a 'Analog Digital Clock' Block.
 *
 * @Block(
 *   id = "analog_digital_clock_skin",
 *   admin_label = @Translation("Analog Digital Clock"),
 * )
 */
class AnalogDigitalClockSkin extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The Config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */

  protected $configfactory;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   Configuration variables in array.
   * @param string $plugin_id
   *   Id of the plugin.
   * @param mixed $plugin_definition
   *   Definition of the plugin.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config variables declairation.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configfactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configfactory->get('analog_digital_clock.settings');
    return [
      '#cache' => ['max-age' => 0],
      '#theme' => 'analogDigitalLightDarkSkin',
      '#analog_digital_clock_selected_skin' => $config->get('analog_digital_clock_skin'),
    ];
  }

}
