<?php

namespace Drupal\images_optimizer\Optimizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Process\Process;

/**
 * Base class for optimizers that rely on the execution of a binary.
 *
 * @package Drupal\images_optimizer\Optimizer
 */
abstract class AbstractProcessOptimizer implements OptimizerInterface {

  use LoggerAwareTrait;

  /**
   * The default timeout if the process optimizer has no configuration.
   *
   * @var int
   */
  const DEFAULT_TIMEOUT = 60;

  /**
   * The process optimizer configuration or null if there is none.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configuration;

  /**
   * AbstractProcessOptimizer constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  final public function __construct(ConfigFactoryInterface $config_factory) {
    $configurationName = $this->getConfigurationName();
    $this->configuration = is_string($configurationName) ? $config_factory->get($configurationName) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  final public function optimize($image_path) {
    $process = new Process($this->getCommandline($image_path), NULL, NULL, NULL, $this->getTimeout());
    try {
      $exit_code = $process->run();
    }
    catch (\RuntimeException $e) {
      $this->logger->error(sprintf('The optimizer process failed with the following message: "%s"', $e->getMessage()));

      return FALSE;
    }

    if (!$this->isSuccess($exit_code)) {
      $this->logger->error(sprintf('The optimizer process failed (exit code: "%s").', $exit_code));

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get the process timeout.
   *
   * @return int
   *   The timeout in seconds. Must be positive.
   */
  public function getTimeout() {
    if (!$this->configuration instanceof ImmutableConfig) {
      return self::DEFAULT_TIMEOUT;
    }

    return intval($this->configuration->get('timeout'));
  }

  /**
   * Should the execution of the process be considered as successful ?
   *
   * @param int $exit_code
   *   The process exit code.
   *
   * @return bool
   *   TRUE if it is the case, FALSE otherwise.
   */
  public function isSuccess($exit_code) {
    return 0 === $exit_code;
  }

  /**
   * Get the complete command line that will be executed.
   *
   * @param string $image_path
   *   The absolute image path.
   *
   * @return string
   *   The complete command line.
   */
  abstract public function getCommandline($image_path);

  /**
   * Get the process optimizer configuration name.
   *
   * A process optimizer should logically always have at least a configurable
   * binary path and a timeout.
   *
   * However, if you do not want to use any configuration at all and want to
   * manually handle options, just return NULL.
   *
   * As a result, the "configuration" property of the process optimizer
   * will be set to NULL.
   *
   * @return string|null
   *   The full configuration name, NULL if there is no configuration.
   */
  abstract public function getConfigurationName();

}
