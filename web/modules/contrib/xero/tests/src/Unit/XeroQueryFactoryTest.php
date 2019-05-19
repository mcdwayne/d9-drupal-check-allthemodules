<?php

namespace Drupal\Tests\xero\Unit;

use Drupal\Core\Cache\NullBackend;
use Drupal\Tests\UnitTestCase;
use Drupal\xero\XeroQueryFactory;

/**
 * Tests the XeroQueryFactory service.
 *
 * @group xero
 */
class XeroQueryFactoryTest extends UnitTestCase {

  /**
   * Xero client mock.
   *
   * @var \Radcliffe\Xero\XeroClient
   */
  protected $xeroClient;

  /**
   * Serializer mock.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Typed Data Manager mock.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * Logger Channel Factory mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Null backend for testing.
   *
   * @var \Drupal\Core\Cache\NullBackend
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $clientProphet = $this->prophesize('\Radcliffe\Xero\XeroClient');
    $this->xeroClient = $clientProphet->reveal();

    $serializerProphet = $this->prophesize('\Symfony\Component\Serializer\Serializer');
    $this->serializer = $serializerProphet->reveal();

    $typedProphet = $this->prophesize('\Drupal\Core\TypedData\TypedDataManagerInterface');
    $this->typedDataManager = $typedProphet->reveal();

    $loggerProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $this->loggerChannelFactory = $loggerProphet->reveal();

    $this->cacheBackend = new NullBackend('cache.xero_query');
  }

  /**
   * Asserts that a new xero query object is created.
   */
  public function testGetOnce() {
    $factory = new XeroQueryFactory($this->xeroClient, $this->serializer, $this->typedDataManager, $this->loggerChannelFactory, $this->cacheBackend);
    $query = $factory->get();
    $this->assertInstanceOf('\Drupal\xero\XeroQuery', $query);
  }

  /**
   * Asserts that a new xero query object is created.
   */
  public function testGetUnique() {
    $factory = new XeroQueryFactory($this->xeroClient, $this->serializer, $this->typedDataManager, $this->loggerChannelFactory, $this->cacheBackend);
    $queryOne = $factory->get();

    $queryOne->addCondition('Type', 'BANK');

    $queryTwo = $factory->get();

    $this->assertEmpty($queryTwo->getConditions());
  }

}
