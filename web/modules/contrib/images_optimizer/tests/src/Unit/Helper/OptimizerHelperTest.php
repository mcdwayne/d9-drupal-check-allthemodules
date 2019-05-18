<?php

namespace Drupal\Tests\images_optimizer\Unit\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\images_optimizer\Form\ConfigurationForm;
use Drupal\images_optimizer\Helper\OptimizerHelper;
use Drupal\images_optimizer\Optimizer\OptimizerInterface;
use Drupal\images_optimizer\ServiceCollector\OptimizerServiceCollector;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Unit test class for the OptimizerHelper class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\Helper
 */
class OptimizerHelperTest extends UnitTestCase {

  /**
   * The optimizer service collector.
   *
   * @var \Drupal\images_optimizer\ServiceCollector\OptimizerServiceCollector
   */
  private $optimizerServiceCollector;

  /**
   * The configuration of our module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $configuration;

  /**
   * The mocked file system.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $fileSystem;

  /**
   * The logger.
   *
   * @var \Symfony\Component\HttpKernel\Tests\Logger
   */
  private $logger;

  /**
   * The optimizer helper to test.
   *
   * @var \Drupal\images_optimizer\Helper\OptimizerHelper
   */
  private $optimizerHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->optimizerServiceCollector = new OptimizerServiceCollector();

    $this->configuration = new ImmutableConfig('', $this->createMock(StorageInterface::class), $this->createMock(EventDispatcherInterface::class), $this->createMock(TypedConfigManagerInterface::class));

    $this->fileSystem = $this->createMock(FileSystemInterface::class);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory
      ->expects($this->atLeastOnce())
      ->method('get')
      ->with(ConfigurationForm::MAIN_CONFIGURATION_NAME)
      ->willReturn($this->configuration);

    $this->logger = new Logger();

    $this->optimizerHelper = new OptimizerHelper($this->optimizerServiceCollector, $this->fileSystem, $configFactory);
    $this->optimizerHelper->setLogger($this->logger);
  }

  /**
   * Test getBySupportedMimeType().
   */
  public function testGetBySupportedMimeType() {
    $optimizer1 = $this->getOptimizer(['mime/type1', 'mime/type2']);
    $optimizer2 = $this->getOptimizer(['mime/type2']);
    $optimizer3 = $this->getOptimizer(['mime/type3']);

    $this->optimizerServiceCollector->add($optimizer1, 'service_1');
    $this->optimizerServiceCollector->add($optimizer2, 'service_2');
    $this->optimizerServiceCollector->add($optimizer3, 'service_3');

    $this->assertSame([
      'mime/type1' => [
        'service_1' => $optimizer1,
      ],
      'mime/type2' => [
        'service_1' => $optimizer1,
        'service_2' => $optimizer2,
      ],
      'mime/type3' => [
        'service_3' => $optimizer3,
      ],
    ], $this->optimizerHelper->getBySupportedMimeType());
  }

  /**
   * Test optimize() when the configured service id is not a string.
   */
  public function testOptimizeWhenTheConfiguredServiceIdIsNotString() {
    $this->configuration->initWithData(['mime/type' => NULL]);

    $this->assertFalse($this->optimizerHelper->optimize('mime/type', 'foo'));
  }

  /**
   * Test optimize().
   *
   * When the configured service id does not resolve to a registered optimizer.
   */
  public function testOptimizeWhenTheConfiguredServiceIdDoesNotResolveToRegisteredOptimizer() {
    $this->configuration->initWithData(['mime/type' => 'foo']);

    $this->assertFalse($this->optimizerHelper->optimize('mime/type', 'bar'));
  }

  /**
   * Test optimize() when the image path cannot be resolved.
   */
  public function testOptimizeWhenTheImagePathCannotBeResolved() {
    $this->prepareOptimizeTest(FALSE);

    $this->assertFalse($this->optimizerHelper->optimize('mime/type', 'public://monespritglisseailleurs'));

    $logs = $this->logger->getLogs('error');
    $this->assertCount(1, $logs);
    $this->assertSame('Could not resolve the path of the image (URI: "public://monespritglisseailleurs").', reset($logs));
  }

  /**
   * Test optimize() when the optimization failed.
   */
  public function testOptimizeWhenTheOptimizationFailed() {
    $optimizer = $this->prepareOptimizeTest(TRUE, FALSE);

    $this->assertFalse($this->optimizerHelper->optimize('mime/type', 'public://monespritglisseailleurs'));

    $logs = $this->logger->getLogs('error');
    $this->assertCount(1, $logs);
    $this->assertSame(sprintf('The optimization failed (optimizer: "%s", image path: "/var/www/html/uploads/mon_image.jpg").', get_class($optimizer)), reset($logs));
  }

  /**
   * Test optimize().
   */
  public function testOptimize() {
    $this->prepareOptimizeTest(TRUE, TRUE);

    $this->assertTrue($this->optimizerHelper->optimize('mime/type', 'public://monespritglisseailleurs'));
  }

  /**
   * Get a mocked optimizer.
   *
   * @param array $supportedMimeTypes
   *   The supported mime types.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mocked optimizer.
   */
  private function getOptimizer(array $supportedMimeTypes = []) {
    $optimizer = $this->createMock(OptimizerInterface::class);
    if (!empty($supportedMimeTypes)) {
      $optimizer
        ->expects($this->atLeastOnce())
        ->method('getSupportedMimeTypes')
        ->willReturn($supportedMimeTypes);
    }

    return $optimizer;
  }

  /**
   * Prepare the context for the optimize() method tests.
   *
   * When it actually reaches the optimize() call.
   *
   * @param bool $pathIsResolved
   *   TRUE if the image path was resolved, FALSE otherwise.
   * @param bool $optimizationIsSuccessful
   *   TRUE if the optimization was successful, FALSE otherwise.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   A mocked optimizer.
   */
  private function prepareOptimizeTest($pathIsResolved, $optimizationIsSuccessful = FALSE) {
    $this->configuration->initWithData(['mime/type' => 'optimizer']);

    $optimizer = $this->getOptimizer();
    $this->optimizerServiceCollector->add($optimizer, 'optimizer');

    $this->fileSystem
      ->expects($this->atLeastOnce())
      ->method('realpath')
      ->with('public://monespritglisseailleurs')
      ->willReturn($pathIsResolved ? '/var/www/html/uploads/mon_image.jpg' : FALSE);

    if ($pathIsResolved) {
      $optimizer
        ->expects($this->atLeastOnce())
        ->method('optimize')
        ->with('/var/www/html/uploads/mon_image.jpg')
        ->willReturn($optimizationIsSuccessful);
    }

    return $optimizer;
  }

}
