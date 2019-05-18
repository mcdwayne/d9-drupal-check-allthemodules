<?php

namespace Drupal\commerce_xero\Plugin\CommerceXero;

use Drupal\commerce_xero\CommerceXeroDataTypePluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides re-usable methods to set and get configuration.
 */
abstract class CommerceXeroDataTypePluginBase extends PluginBase implements CommerceXeroDataTypePluginInterface {

  protected $configuration;

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
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);

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

}
