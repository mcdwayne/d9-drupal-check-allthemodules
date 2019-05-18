<?php

namespace Drupal\commerce_funds;

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
    parent::__construct('Plugin/Funds/WithdrawalMethod', $namespaces, $module_handler, 'Drupal\commerce_funds\WithdrawalMethodInterface', 'Drupal\commerce_funds\Annotation\WithdrawalMethod');

    $this->alterInfo('commerce_funds_withdrawal_methods_info');
    $this->setCacheBackend($cache_backend, 'commerce_funds_withdrawal_methods');
  }

}
