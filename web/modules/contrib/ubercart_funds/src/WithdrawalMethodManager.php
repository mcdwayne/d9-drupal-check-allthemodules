<?php

namespace Drupal\ubercart_funds;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Withdrawal method plugin manager.
 */
class WithdrawalMethodManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FundsWithdrawalMethod', $namespaces, $module_handler, 'Drupal\ubercart_funds\WithdrawalMethodInterface', 'Drupal\ubercart_funds\Annotation\WithdrawalMethod');

    $this->alterInfo('uc_funds_withdrawal_methods_info');
    $this->setCacheBackend($cache_backend, 'uc_funds_withdrawal_methods');
  }

  /**
   * Return enabled full withdrawal methods.
   *
   * @see UserFundsWithdrawalMethods
   *
   * @return array
   *   Enabled withdrawal methods.
   */
  public function getEnabledWithdrawalMethods() {
    // Get config set by admin.
    $enabled_methods = \Drupal::config('uc_funds.settings')->get('withdrawal_methods')['methods'];
    // Get full methods.
    $method_defs = $this->getDefinitions();
    // Filter enabled methods in method definitions.
    $methods = array_intersect_key($method_defs, array_flip($enabled_methods));

    return $methods;

  }

}
