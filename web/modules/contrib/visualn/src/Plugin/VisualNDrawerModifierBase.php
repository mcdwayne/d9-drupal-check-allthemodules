<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for VisualN Drawer Modifier plugins.
 */
//abstract class VisualNDrawerModifierBase extends PluginBase implements VisualNDrawerModifierInterface, ContainerFactoryPluginInterface {
abstract class VisualNDrawerModifierBase extends PluginBase implements VisualNDrawerModifierInterface {

  /**
   * The drawer modifier ID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The weight of the drawer modifier.
   *
   * @var int|string
   */
  protected $weight = '';


  /**
   * {@inheritdoc}
   */
  //public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    // @todo: see ImageEffectBase::__construct()
    //$this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: add all these methods to interface
   */
  public function getSummary() {
    return [
      '#markup' => '',
      // @todo:
      '#modifier' => [
        'id' => $this->pluginDefinition['id'],
        'label' => $this->label(),
        'description' => $this->pluginDefinition['description'],
      ],
    ];
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
  public function getUuid() {
    return $this->uuid;
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
  public function getWeight() {
    return $this->weight;
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
  public function calculateDependencies() {
    return [];
  }

  /**
   * @todo: add to the base class and interface
   * @todo: maybe rename the method
   */
  public function methodsSubstitutionsInfo() {
    return [];
  }

/*
  public function applyModifier($method, $args) {
    // @todo: get all args
  }
*/


  // Add common methods and abstract methods for your plugin type here.

}
