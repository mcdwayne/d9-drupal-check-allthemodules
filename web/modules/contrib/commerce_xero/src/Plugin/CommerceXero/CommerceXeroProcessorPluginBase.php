<?php

namespace Drupal\commerce_xero\Plugin\CommerceXero;

use Drupal\commerce_xero\CommerceXeroProcessorPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Commerce Xero processor plugin base class.
 */
abstract class CommerceXeroProcessorPluginBase extends PluginBase implements CommerceXeroProcessorPluginInterface {
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
