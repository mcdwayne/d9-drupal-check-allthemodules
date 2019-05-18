<?php

namespace Drupal\Tests\images_optimizer\Unit\Optimizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Base test class for classes that extend the AbstractProcessOptimizer class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\Optimizer
 */
abstract class AbstractProcessOptimizerTestCase extends UnitTestCase {

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configuration;

  /**
   * The process optimizer to test.
   *
   * @var \Drupal\images_optimizer\Optimizer\AbstractProcessOptimizer
   */
  protected $processOptimizer;

  /**
   * The logger.
   *
   * @var \Symfony\Component\HttpKernel\Tests\Logger
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configuration = new ImmutableConfig('', $this->createMock(StorageInterface::class), $this->createMock(EventDispatcherInterface::class), $this->createMock(TypedConfigManagerInterface::class));

    $this->logger = new Logger();

    $this->processOptimizer = $this->getProcessOptimizer(TRUE);
  }

  /**
   * Get a process optimizer.
   *
   * @param bool $withConfiguration
   *   TRUE if the process optimizer has a configuration, FALSE otherwise.
   *
   * @return \Drupal\images_optimizer\Optimizer\AbstractProcessOptimizer
   *   The process optimizer.
   */
  protected function getProcessOptimizer($withConfiguration) {
    $processOptimizerClass = $this->getProcessOptimizerClass();

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory
      ->expects($this->atLeastOnce())
      ->method('get')
      ->with((new $processOptimizerClass($this->createMock(ConfigFactoryInterface::class)))->getConfigurationName())
      ->willReturn($withConfiguration ? $this->configuration : NULL);

    /** @var \Drupal\images_optimizer\Optimizer\AbstractProcessOptimizer $processOptimizer */
    $processOptimizer = new $processOptimizerClass($configFactory);
    $processOptimizer->setLogger($this->logger);

    return $processOptimizer;
  }

  /**
   * Get the full qualified class name of the process optimizer to test.
   *
   * @return string
   *   The process optimizer FQCN.
   */
  abstract protected function getProcessOptimizerClass();

}
