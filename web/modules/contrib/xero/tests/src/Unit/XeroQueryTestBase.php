<?php

namespace Drupal\Tests\xero\Unit;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\serialization\Encoder\XmlEncoder;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\serialization\Normalizer\TypedDataNormalizer;
use Drupal\Tests\UnitTestCase;
use Drupal\xero\XeroQuery;
use Drupal\xero\Normalizer\XeroNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class XeroQueryTestBase provides helper methods for unit tests.
 */
abstract class XeroQueryTestBase extends UnitTestCase {

  /**
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * @var \Psr\Log\LoggerInterface;
   */
  protected $loggerFactory;

  /**
   * @var \Radcliffe\Xero\XeroClient
   */
  protected $client;

  /**
   * @var \Drupal\Core\Cache\NullBackend
   */
  protected $cache;

  /**
   * @var \Drupal\xero\XeroQuery
   */
  protected $query;

  protected function setUp() {
    parent::setUp();

    // Setup a Null cache backend.
    $this->cache = $this->getMockBuilder('Drupal\Core\Cache\NullBackend')
      ->disableOriginalConstructor()
      ->getMock();

    // Setup LoggerChannelFactory.
    $loggerProphet = $this->prophesize('\Psr\Log\LoggerInterface');
    $this->loggerFactory = new LoggerChannelFactory();
    $this->loggerFactory->addLogger($loggerProphet->reveal());

    // Mock the Typed Data Manager.
    $this->typedDataManager = $this->getMockBuilder('Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();

    // Mock XeroClient.
    $this->client = $this->getMockBuilder('Radcliffe\Xero\XeroClient')
      ->disableOriginalConstructor()
      ->getMock();

    // Setup the container.
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $this->typedDataManager);
    $container->set('serializer', $this->serializer);
    \Drupal::setContainer($container);

    // Setup the Serializer component class.
    $this->serializer = new Serializer([
      new ComplexDataNormalizer(),
      new XeroNormalizer($this->typedDataManager),
      new TypedDataNormalizer(),
    ], [
      new XmlEncoder(),
    ]);

    $this->query = new XeroQuery($this->client, $this->serializer, $this->typedDataManager, $this->loggerFactory, $this->cache);
  }

  /**
   * Create a Guid.
   *
   * @return string
   *   A valid globally-unique identifier.
   */
  protected function createGuid($braces = TRUE) {
    $hash = strtoupper(hash('ripemd128', md5($this->getRandomGenerator()->string(100))));
    $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4);
    $guid .= '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);

    // A Guid string representation should be output as lower case per UUIDs
    // and GUIDs Network Working Group INTERNET-DRAFT 3.3.
    $guid = strtolower($guid);

    return $guid;
  }
}
