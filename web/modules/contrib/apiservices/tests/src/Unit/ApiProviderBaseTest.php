<?php

/**
 * @file
 * Contains \Drupal\Tests\apiservices\Unit\ApiProviderBaseTest.
 */

namespace Drupal\Tests\apiservices\Unit;

use Drupal\apiservices\ApiProviderBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @group apiservices
 */
class ApiProviderBaseTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Initialize the container.
    $this->entityStorage = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->entityStorage);

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    \Drupal::setContainer($container);
  }

  /**
   * Creates an API provider with mocked abstract methods.
   *
   * @param string $url
   *   (optional) The URL that the provider will generate.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mocked API provider.
   */
  protected function mockProvider($url = '') {
    $mock = $this->getMockBuilder(ApiProviderBase::class)
      ->disableOriginalConstructor()
      ->setMethods(['getRequestUrl'])
      ->getMockForAbstractClass();
    $mock->expects($this->any())
      ->method('getRequestUrl')
      ->willReturn($url);
    return $mock;
  }

  /**
   * Tests that negative cache lifetimes throw an exception.
   *
   * @expectedException \DomainException
   */
  public function testInvalidCacheLifetime() {
    $this->entityStorage->expects($this->any())
      ->method('load')
      ->willReturn($this->getMock('Drupal\apiservices\Entity\EndpointInterface'));

    $mock = $this->mockProvider('http://example.com');
    $mock->__construct('test');
    $mock->setCacheLifetime(-60);
  }

  /**
   * Tests that using an invalid endpoint entity throws an exception.
   *
   * @expectedException \UnexpectedValueException
   */
  public function testInvalidEndpoint() {
    $this->entityStorage->expects($this->any())
      ->method('load')
      ->willReturn(NULL);
    $this->mockProvider()->__construct('test');
  }

  /**
   * Tests the creation of an API provider.
   */
  public function testProvider() {
    $endpoint = $this->getMock('Drupal\apiservices\Entity\EndpointInterface');
    $endpoint->expects($this->any())
      ->method('id')
      ->willReturn('test');
    $this->entityStorage->expects($this->any())
      ->method('load')
      ->with('test')
      ->willReturn($endpoint);

    $mock = $this->mockProvider('http://example.com');
    $mock->__construct('test');
    $this->assertEquals('test', $mock->getEndpoint()->id());
    $this->assertEquals('example.com', $mock->getRequest()->getUri()->getHost());
  }

  /**
   * Tests the cache methods of the API provider.
   */
  public function testProviderCache() {
    $this->entityStorage->expects($this->any())
      ->method('load')
      ->willReturn($this->getMock('Drupal\apiservices\Entity\EndpointInterface'));

    $mock = $this->mockProvider('http://example.com');
    $mock->__construct('test');

    $this->assertEquals(':http://example.com', $mock->getCacheId());
    $mock->setCacheContext('module');
    $this->assertEquals('module:http://example.com', $mock->getCacheId());
    $this->assertEquals('module', $mock->getCacheContext());
    $mock->setCacheLifetime(0);
    $this->assertEquals(0, $mock->getCacheLifetime());
    $mock->setCacheLifetime(CacheBackendInterface::CACHE_PERMANENT);
    $this->assertEquals(CacheBackendInterface::CACHE_PERMANENT, $mock->getCacheLifetime());
  }

}
