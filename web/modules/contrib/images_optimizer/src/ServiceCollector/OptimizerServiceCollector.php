<?php

namespace Drupal\images_optimizer\ServiceCollector;

use Drupal\images_optimizer\Optimizer\OptimizerInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Service collector for the optimizers.
 *
 * @package Drupal\images_optimizer
 */
class OptimizerServiceCollector {

  use LoggerAwareTrait;

  /**
   * The collected optimizers indexed by their service id.
   *
   * @var \Drupal\images_optimizer\Optimizer\OptimizerInterface[]
   */
  private $optimizers;

  /**
   * OptimizerServiceCollector constructor.
   */
  public function __construct() {
    $this->optimizers = [];
  }

  /**
   * Get all the collected optimizers, indexed by their service id.
   *
   * @return \Drupal\images_optimizer\Optimizer\OptimizerInterface[]
   *   The optimizers.
   */
  public function all() {
    return $this->optimizers;
  }

  /**
   * Get the optimizer for a service id.
   *
   * @param string $service_id
   *   The service id.
   *
   * @return \Drupal\images_optimizer\Optimizer\OptimizerInterface|null
   *   The matching optimizer, NULL if there is none.
   */
  public function get($service_id) {
    if (!isset($this->optimizers[$service_id])) {
      return NULL;
    }

    return $this->optimizers[$service_id];
  }

  /**
   * Add an optimizer.
   *
   * @param \Drupal\images_optimizer\Optimizer\OptimizerInterface $optimizer
   *   The optimizer.
   * @param string $id
   *   The service id.
   */
  public function add(OptimizerInterface $optimizer, $id) {
    if (isset($this->optimizers[$id])) {
      throw new \InvalidArgumentException('An optimizer with the same service id has already been added.');
    }

    $this->optimizers[$id] = $optimizer;
  }

}
