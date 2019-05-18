<?php

namespace Drupal\Tests\images_optimizer\Unit\Optimizer;

use Drupal\images_optimizer\Optimizer\PngquantOptimizer;

/**
 * Unit test class for the PngquantOptimizer class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\Optimizer
 */
class PngquantOptimizerTest extends AbstractProcessOptimizerTestCase {

  /**
   * {@inheritdoc}
   */
  protected function getProcessOptimizerClass() {
    return PngquantOptimizer::class;
  }

  /**
   * Test getSupportedMimeTypes().
   */
  public function testGetSupportedMimeTypes() {
    $this->assertSame(['image/png'], $this->processOptimizer->getSupportedMimeTypes());
  }

  /**
   * Test getName().
   */
  public function testGetName() {
    $this->assertSame('Pngquant', $this->processOptimizer->getName());
  }

  /**
   * Test getTimeout().
   */
  public function testGetTimeout() {
    $this->configuration->initWithData(['timeout' => 10]);

    $this->assertSame(10, $this->processOptimizer->getTimeout());
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
    // Quoting pngquant documentation : If conversion results in quality below
    // the min quality the image won't be saved and pngquant will exit with
    // status code 99.
    // We consider this scenario as a failed optimization.
    return [
      [TRUE, 0],
      [FALSE, 1],
      [FALSE, 99],
    ];
  }

  /**
   * Test getCommandLine().
   */
  public function testGetCommandline() {
    $this->configuration->initWithData([
      'binary_path' => '/bin/pngquant',
      'minimum_quality' => 20,
      'maximum_quality' => 90,
    ]);

    $this->assertSame('/bin/pngquant --strip --quality 20-90 --skip-if-larger --force --output /home/foo/bar/heavy.png /home/foo/bar/heavy.png', $this->processOptimizer->getCommandline('/home/foo/bar/heavy.png'));
  }

  /**
   * Test getConfigurationName().
   */
  public function testGetConfigurationName() {
    $this->assertSame('images_optimizer.pngquant.settings', $this->processOptimizer->getConfigurationName());
  }

}
