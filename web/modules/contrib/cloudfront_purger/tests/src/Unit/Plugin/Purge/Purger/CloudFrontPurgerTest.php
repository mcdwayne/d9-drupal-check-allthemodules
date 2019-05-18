<?php

namespace Drupal\Tests\cloudfront_purger\Unit\Plugin\Purge\Purger;

use Drupal\cloudfront_purger\CloudFrontInvalidatorInterface;
use Drupal\cloudfront_purger\Plugin\Purge\Purger\CloudFrontPurger;
use Drupal\purge\Plugin\Purge\Invalidation\PathInvalidation;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for CloudFrontPurger.
 *
 * @coversDefaultClass \Drupal\cloudfront_purger\Plugin\Purge\Purger\CloudFrontPurger
 * @group cloudfront_purger
 */
class CloudFrontPurgerTest extends UnitTestCase {

  protected $runTestInSeparateProcess = TRUE;

  /**
   * The purger under test.
   *
   * @var \Drupal\cloudfront_purger\Plugin\Purge\Purger\CloudFrontPurger
   */
  protected $purger;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The cloudfront invalidator.
   *
   * @var \Drupal\cloudfront_purger\CloudFrontInvalidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $invalidator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->configFactory = $this->getConfigFactoryStub([
      'cloudfront_purger.settings' => [
        'distribution_id' => 'ABCDEFG12345678',
      ],
    ]);

    $this->invalidator = $this->createMock(CloudFrontInvalidatorInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $configuration = [
      'id' => 'cloudfront',
    ];
    $definition = [
      'id' => 'cloudfront',
      'label' => 'CloudFront',
    ];

    $this->purger = new CloudFrontPurger($configuration, 'cloudfront', $definition, $this->invalidator, $this->configFactory, $this->logger);
  }

  /**
   * Tests paths.
   *
   * @covers ::invalidate
   */
  public function testInvalidatePaths() {

    $invalidations = [
      $this->createInvalidation('node/1'),
      $this->createInvalidation('blog/blog-title'),
    ];

    $this->logger->expects($this->never())
      ->method('info');

    $this->invalidator->expects($this->once())
      ->method('invalidate')
      ->willReturn('ABC123');

    $this->purger->invalidate($invalidations);

  }

  /**
   * Creats a path invalidation for testing.
   *
   * @param string $path
   *   The test path.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\PathInvalidation
   *   The invalidation.
   */
  protected function createInvalidation($path) {
    $configuration = [
      'id' => 'path',
    ];
    $definition = [
      'id' => 'path',
      'label' => 'Path Invalidation',
      'expression_required' => TRUE,
    ];
    $invalidation = new PathInvalidation($configuration, 'path', $definition, '', $path);
    $invalidation->setStateContext('path');
    return $invalidation;
  }

}
