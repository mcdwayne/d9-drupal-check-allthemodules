<?php
namespace Drupal\commerce_rental_reservation;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides an Rate Calculator plugin manager.
 *
 * @see \Drupal\commerce_rental\Annotation\RentalInstanceSelector
 * @see \Drupal\commerce_rental_reservation\Plugin\Commerce\RentalInstanceSelector\RentalInstanceSelectorPluginInterface
 * @see plugin_api
 */

class RentalInstanceSelectorManager extends DefaultPluginManager {

   public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Commerce/RentalInstanceSelector',
      $namespaces,
      $module_handler,
      'Drupal\commerce_rental_reservation\Plugin\Commerce\RentalInstanceSelector\RentalInstanceSelectorPluginInterface',
      'Drupal\commerce_rental_reservation\Annotation\RentalInstanceSelector'
    );
    $this->alterInfo('rental_instance_selector_info');
    $this->setCacheBackend($cache_backend, 'rental_instance_selector_plugins');
  }

}