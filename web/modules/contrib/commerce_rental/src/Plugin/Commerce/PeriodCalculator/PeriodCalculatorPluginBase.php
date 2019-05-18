<?php

namespace Drupal\commerce_rental\Plugin\Commerce\PeriodCalculator;

use Drupal\commerce_rental\PeriodCalculatorResponse;
use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class PeriodCalculatorPluginBase extends PluginBase implements PeriodCalculatorPluginInterface {

  /**
   * Constructs a PeriodCalculatorPluginBase object.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculate($start_date, $end_date, $period) {
    return new PeriodCalculatorResponse(0, $start_date);
  }
}