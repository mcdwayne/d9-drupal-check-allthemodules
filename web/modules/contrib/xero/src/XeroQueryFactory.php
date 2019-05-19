<?php

namespace Drupal\xero;

use Drupal\Core\TypedData\TypedDataManagerInterface;
use Radcliffe\Xero\XeroClient;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\Serializer\Serializer;

class XeroQueryFactory {

  /**
   * XeroClient.
   *
   * @var \Radcliffe\Xero\XeroClient
   */
  protected $client;

  /**
   * Serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Typed Data Manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * Logger Channel Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Cache back end.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Initialize method.
   *
   * @param \Radcliffe\Xero\XeroClient $client
   *   The xero.client service.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend to use.
   */
  public function __construct(XeroClient $client, Serializer $serializer, TypedDataManagerInterface $typedDataManager, LoggerChannelFactoryInterface $loggerChannelFactory, CacheBackendInterface $cacheBackend) {
    $this->client = $client;
    $this->serializer = $serializer;
    $this->typedDataManager = $typedDataManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * Get a new XeroQuery instance.
   *
   * @return \Drupal\xero\XeroQuery
   *   A new xero query object instance.
   */
  public function get() {
    return new XeroQuery(
      $this->client,
      $this->serializer,
      $this->typedDataManager,
      $this->loggerChannelFactory,
      $this->cacheBackend
    );
  }

}
