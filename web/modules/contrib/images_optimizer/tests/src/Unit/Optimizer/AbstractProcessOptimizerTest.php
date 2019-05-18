<?php

namespace Drupal\Tests\images_optimizer\Unit\Optimizer;

use Drupal\images_optimizer\Optimizer\AbstractProcessOptimizer;

/**
 * Unit test class for the AbstractProcessOptimizer class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\Optimizer
 */
class AbstractProcessOptimizerTest extends AbstractProcessOptimizerTestCase {

  /**
   * The process optimizer to test.
   *
   * @var \Drupal\Tests\images_optimizer\Unit\Optimizer\TestProcessOptimizer
   */
  protected $processOptimizer;

  /**
   * {@inheritdoc}
   */
  protected function getProcessOptimizerClass() {
    return TestProcessOptimizer::class;
  }

  /**
   * Test optimize() when the process failed.
   */
  public function testOptimizeWhenTheProcessFailed() {
    $this->processOptimizer->setCommandLine('exit 47');

    $this->assertFalse($this->processOptimizer->optimize('foo'));

    $logs = $this->logger->getLogs('error');
    $this->assertCount(1, $logs);
    $this->assertSame('The optimizer process failed (exit code: "47").', reset($logs));
  }

  /**
   * Test optimize() when the process was successful.
   */
  public function testOptimizeWhenTheProcessWasSuccessful() {
    $this->processOptimizer->setCommandLine('exit 0');

    $this->assertTrue($this->processOptimizer->optimize('foo'));
  }

  /**
   * Test getTimeout() when there is no configuration.
   */
  public function testGetTimeoutWhenThereIsNoConfiguration() {
    $this->assertSame(60, $this->getProcessOptimizer(FALSE)->getTimeout());
  }

  /**
   * Test getTimeout() when there is a configuration.
   */
  public function testGetTimeoutWhenThereIsConfiguration() {
    $this->configuration->initWithData(['timeout' => 25]);

    $this->assertSame(25, $this->processOptimizer->getTimeout());
  }

  /**
   * Test isSuccess().
   *
   * @dataProvider isSuccessProvider
   */
  public function testIsSuccess($expected, $exit_code) {
    $this->assertSame($expected, $this->processOptimizer->isSuccess($exit_code));
  }

  /**
   * Provide the data for the testIsSuccess() method.
   *
   * @return array
   *   The data.
   */
  public function isSuccessProvider() {
    return [
      [TRUE, 0],
      [FALSE, 1],
    ];
  }

}

/**
 * Test class of Drupal\images_optimizer\Optimizer\AbstractProcessOptimizer.
 *
 * @package Drupal\Tests\images_optimizer\Unit\Optimizer
 */
class TestProcessOptimizer extends AbstractProcessOptimizer {

  /**
   * The command line.
   *
   * @var string
   */
  private $commandLine;

  /**
   * {@inheritdoc}
   */
  public function getSupportedMimeTypes() {
    return ['image/fcy'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'foo';
  }

  /**
   * {@inheritdoc}
   */
  public function getCommandline($image_path) {
    return $this->commandLine;
  }

  /**
   * Set the command line manually for the test.
   *
   * @param string $command_line
   *   The command line.
   */
  public function setCommandLine($command_line) {
    $this->commandLine = $command_line;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationName() {
    return 'bar';
  }

}
