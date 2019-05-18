<?php
namespace Drupal\commerce_rental;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides an Period Calculator plugin manager.
 *
 * @see \Drupal\commerce_rental\Annotation\PeriodCalculator
 * @see \Drupal\commerce_rental\Plugin\Commerce\PeriodCalculator\PeriodCalculatorPluginInterface
 * @see plugin_api
 */

class PeriodCalculatorManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Commerce/PeriodCalculator',
      $namespaces,
      $module_handler,
      'Drupal\commerce_rental\Plugin\Commerce\PeriodCalculator\PeriodCalculatorPluginInterface',
      'Drupal\commerce_rental\Annotation\PeriodCalculator'
    );
    $this->alterInfo('period_calculator_info');
    $this->setCacheBackend($cache_backend, 'period_calculator_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }
}