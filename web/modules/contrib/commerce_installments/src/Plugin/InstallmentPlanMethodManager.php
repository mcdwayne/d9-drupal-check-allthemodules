<?php

namespace Drupal\commerce_installments\Plugin;

use Drupal\commerce_installments\Annotation\InstallmentPlan;
use Drupal\commerce_installments\Plugin\Commerce\InstallmentPlanMethod\InstallmentPlanMethodInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Installment Plan Method plugin manager.
 */
class InstallmentPlanMethodManager extends DefaultPluginManager {

  /**
   * Default values for each installment plan plugin.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
  ];

  /**
   * Constructs a new InstallmentPlanManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Commerce/InstallmentPlanMethod', $namespaces, $module_handler, InstallmentPlanMethodInterface::class, InstallmentPlan::class);

    $this->alterInfo('commerce_installment_plan_info');
    $this->setCacheBackend($cache_backend, 'commerce_installment_plan_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (array_keys($this->defaults) as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The installment %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
