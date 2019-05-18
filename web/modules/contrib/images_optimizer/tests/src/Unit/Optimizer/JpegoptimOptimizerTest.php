<?php

namespace Drupal\Tests\images_optimizer\Unit\Optimizer;

use Drupal\images_optimizer\Optimizer\JpegoptimOptimizer;

/**
 * Unit test class for the JpegoptimOptimizer class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\Optimizer
 */
class JpegoptimOptimizerTest extends AbstractProcessOptimizerTestCase {

  /**
   * {@inheritdoc}
   */
  protected function getProcessOptimizerClass() {
    return JpegoptimOptimizer::class;
  }

  /**
   * Test getSupportedMimeTypes().
   */
  public function testGetSupportedMimeTypes() {
    $this->assertSame(['image/jpeg'], $this->processOptimizer->getSupportedMimeTypes());
  }

  /**
   * Test getName().
   */
  public function testGetName() {
    $this->assertSame('Jpegoptim', $this->processOptimizer->getName());
  }

  /**
   * Test getTimeout().
   */
  public function testGetTimeout() {
    $this->configuration->initWithData(['timeout' => 5]);

    $this->assertSame(5, $this->processOptimizer->getTimeout());
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

  /**
   * Test getCommandLine().
   */
  public function testGetCommandline() {
    $this->configuration->initWithData([
      'binary_path' => '/usr/bin/jpegoptim',
      'quality' => 50,
    ]);

    $this->assertSame('/usr/bin/jpegoptim --strip-all --max=50 --preserve --preserve-perms /var/www/web/sites/default/files/image_up.jpg', $this->processOptimizer->getCommandline('/var/www/web/sites/default/files/image_up.jpg'));
  }

  /**
   * Test getConfigurationName().
   */
  public function testGetConfigurationName() {
    $this->assertSame('images_optimizer.jpegoptim.settings', $this->processOptimizer->getConfigurationName());
  }

}
