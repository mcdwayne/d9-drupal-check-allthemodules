<?php

namespace Drupal\Tests\cloudfront_purger\Unit;

use Aws\CloudFront\CloudFrontClient;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\Result;
use Drupal\cloudfront_purger\CloudFrontInvalidator;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for CloudFrontInvalidator,.
 *
 * @coversDefaultClass \Drupal\cloudfront_purger\CloudFrontInvalidator
 * @group cloudfront_purger
 */
class CloudFrontInvalidatorTest extends UnitTestCase {

  /**
   * The invalidator under test.
   *
   * @var \Drupal\cloudfront_purger\CloudFrontInvalidator
   */
  protected $invalidator;

  /**
   * The CloudFront client.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Aws\CloudFront\CloudFrontClient
   */
  protected $client;

  /**
   * The logger.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->client = $this->getMockBuilder(CloudFrontClient::class)
      ->disableOriginalConstructor()
      ->setMethods(['createInvalidation'])
      ->getMock();
    $this->invalidator = new CloudFrontInvalidator($this->client, $this->logger);
  }

  /**
   * Tests invalidate.
   *
   * @covers ::invalidate
   */
  public function testInvalidate() {

    $this->logger->expects($this->once())
      ->method('info');

    $result = new Result([
      'Invalidation' => ['Id' => 'ABCD1234'],
    ]);

    $this->client->expects($this->once())
      ->method('createInvalidation')
      ->withAnyParameters()
      ->willReturn($result);

    $paths = ['node/1', 'blog/*', '/'];
    $distribution_id = 'ABCD1234';

    $id = $this->invalidator->invalidate($paths, $distribution_id);

    $this->assertEquals('ABCD1234', $id);
  }

  /**
   * Tests an error.
   *
   * @covers ::invalidate
   * @expectedException \Aws\Exception\AwsException
   */
  public function testInvalidateError() {
    $this->logger->expects($this->never())
      ->method('iinfo');

    /* @var \PHPUnit_Framework_MockObject_MockObject|\Aws\CommandInterface $command */
    $command = $this->createMock(CommandInterface::class);

    $this->client->method('createInvalidation')
      ->will($this->throwException(new AwsException('test message', $command)));

    $paths = ['node/1', 'blog/*', '/'];
    $distribution_id = 'ABCD1234';

    $this->invalidator->invalidate($paths, $distribution_id);

  }

}
