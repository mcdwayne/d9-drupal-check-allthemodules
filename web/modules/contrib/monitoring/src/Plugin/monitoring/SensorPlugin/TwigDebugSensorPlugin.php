<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\TwigDebugSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Monitors the twig debug settings.
 *
 * @SensorPlugin(
 *   id = "twig_debug_mode",
 *   label = @Translation("Twig debug mode"),
 *   description = @Translation("Verifies that twig debug settings are disabled."),
 *   addable = FALSE,
 * )
 */
class TwigDebugSensorPlugin extends SensorPluginBase {

  /**
   * Holds the container instance.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  public function __construct(SensorConfig $sensor_config, $plugin_id, $plugin_definition, ContainerInterface $container) {
    parent::__construct($sensor_config, $plugin_id, $plugin_definition, $container);
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, SensorConfig $sensor_config, $plugin_id, $plugin_definition) {
    return new static(
      $sensor_config,
      $plugin_id,
      $plugin_definition,
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $twig_config = $this->container->getParameter('twig.config');
    $result->setStatus(SensorResultInterface::STATUS_OK);

    if (!empty($twig_config['debug'])) {
      $result->setStatus(SensorResultInterface::STATUS_WARNING);
      $result->addStatusMessage('Twig debug mode is enabled');
    }
    if (isset($twig_config['cache']) && !$twig_config['cache']) {
      $result->setStatus(SensorResultInterface::STATUS_WARNING);
      $result->addStatusMessage('Twig cache disabled');
    }
    if (!empty($twig_config['auto_reload'])) {
      $result->setStatus(SensorResultInterface::STATUS_WARNING);
      $result->addStatusMessage('Automatic recompilation of Twig templates enabled');
    }
    $sensor_status = $result->getStatus();
    if ($sensor_status == SensorResultInterface::STATUS_OK) {
      $result->addStatusMessage('Optimal configuration');
    }
  }

}
