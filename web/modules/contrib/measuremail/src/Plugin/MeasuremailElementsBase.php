<?php

namespace Drupal\measuremail\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\measuremail\MeasuremailElementsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for measuremail elements.
 *
 * @see \Drupal\measuremail\Annotation\MeasuremailElements
 * @see \Drupal\measuremail\MeasuremailElementsInterface
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementInterface
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementBase
 * @see \Drupal\measuremail\Plugin\MeasuremailElementsManager
 * @see plugin_api
 */
abstract class MeasuremailElementsBase extends PluginBase implements MeasuremailElementsInterface, ContainerFactoryPluginInterface {

  /**
   * The measuremail element ID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The weight of the measuremail element.
   *
   * @var int|string
   */
  protected $weight = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'data' => [],
      'uuid' => '',
      'weight' => '',
    ];
    $this->configuration = $configuration['data'] + $this->defaultConfiguration();
    $this->uuid = $configuration['uuid'];
    $this->weight = $configuration['weight'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

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
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'uuid' => $this->getUuid(),
      'id' => $this->getPluginId(),
      'weight' => $this->getWeight(),
      'data' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Gets the Measuremail settings.
   *
   * @return array
   *   An array with the measuremail settings.
   */
  public function getSettings() {
    return $this->getSettings();
  }

}
