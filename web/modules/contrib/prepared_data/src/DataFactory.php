<?php

namespace Drupal\prepared_data;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Session\AccountInterface;
use Drupal\prepared_data\Provider\ProviderManager;
use Drupal\prepared_data\Processor\ProcessorManager;

/**
 * Defines the factory for prepared data.
 */
class DataFactory implements DataFactoryInterface {

  /**
   * The manager for data provider plugins.
   *
   * @var \Drupal\prepared_data\Provider\ProviderManager
   */
  protected $providerManager;

  /**
   * The manager for data processor plugins.
   *
   * @var \Drupal\prepared_data\Processor\ProcessorManager
   */
  protected $processorManager;

  /**
   * DataFactory constructor.
   *
   * @param \Drupal\prepared_data\Provider\ProviderManager $provider_manager
   *   The manager for data provider plugins.
   * @param \Drupal\prepared_data\Processor\ProcessorManager
   *   The manager for data processor plugins.
   */
  public function __construct(ProviderManager $provider_manager, ProcessorManager $processor_manager) {
    $this->providerManager = $provider_manager;
    $this->processorManager = $processor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get($argument, $force_valid = FALSE, $force_fresh = FALSE, AccountInterface $account = NULL) {
    $prepared = NULL;
    foreach ($this->providerManager->getAllProviders() as $provider) {
      $current_user = $provider->getCurrentUser();
      if (isset($account)) {
        $provider->setCurrentUser($account);
      }
      if (TRUE === $force_fresh) {
        $prepared = $provider->demandFresh($argument);
      }
      else {
        $prepared = $provider->demand($argument, $force_valid);
      }
      $provider->setCurrentUser($current_user);
      if ($prepared) {
        break;
      }
    }
    return $prepared;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessed($argument, $active_processors = [], $subset_keys = [], $force_valid = FALSE, $force_fresh = FALSE, AccountInterface $account = NULL) {
    $processed = NULL;
    $to_reset = [];

    if (empty($active_processors)) {
      $definitions = $this->processorManager->getDefinitions();
      $active_processors = array_keys($definitions);
    }

    foreach ($active_processors as $id) {
      $processor = $this->processorManager->createInstance($id);
      if ($processor->isEnabled()) {
        if (!$processor->isActive()) {
          $to_reset[] = $processor;
          $processor->setActive(TRUE);
        }
      }
    }

    $prepared = $this->get($argument, $force_valid, $force_fresh, $account);

    if ($force_fresh || !$prepared || empty($subset_keys)) {
      $processed = $prepared;
    }

    if ($prepared && !empty($subset_keys)) {
      $processed = NULL;
      $data = &$prepared->data();
      $subset = $prepared->get($subset_keys);
      if ($force_fresh || (is_string($subset_keys) && isset($data[$subset_keys]))) {
        $processed = $subset;
      }
      elseif (is_array($subset_keys)) {
        $missing = FALSE;
        foreach ($subset_keys as $sk) {
          $is_string = is_string($sk);
          if ($is_string && strpos($sk, ':') === FALSE) {
            if (!isset($data[$sk])) {
              $missing = TRUE;
              break;
            }
          }
          elseif ($is_string || is_array($sk)) {
            $sk_parents = $is_string ? explode(':', $sk) : $sk;
            if (!NestedArray::keyExists($data, $sk_parents)) {
              $missing = TRUE;
              break;
            }
          }
          else {
            throw new \InvalidArgumentException('The given subset keys argument does not contain valid data types. Keys must be strings or arrays of strings (got ' . gettype($sk) . ').');
          }
        }
        if (!$missing) {
          $processed = $subset;
        }
      }
    }

    if (!$force_fresh && !isset($processed)) {
      $processed = $this->getProcessed($argument, $active_processors, $subset_keys, $force_valid, TRUE, $account);
    }
    elseif (isset($subset)) {
      $processed = $subset;
    }
    else {
      $processed = $prepared;
    }

    foreach ($to_reset as $processor) {
      $processor->setActive(FALSE);
    }

    return $processed;
  }

}
