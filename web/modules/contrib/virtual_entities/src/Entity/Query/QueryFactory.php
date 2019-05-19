<?php

namespace Drupal\virtual_entities\Entity\Query;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\virtual_entities\VirtualEntityDecoderServiceInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class QueryFactory.
 *
 * @package Drupal\virtual_entities\Entity\Query
 */
class QueryFactory implements QueryFactoryInterface {

  /**
   * The namespace of this class, the parent class etc.
   *
   * @var array
   */
  protected $namespaces;

  /**
   * The HTTP client to fetch the data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The storage client manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $storageClientManager;

  /**
   * The decoder to decode the data.
   *
   * @var \Drupal\virtual_entities\VirtualEntityDecoderService
   */
  protected $decoder;

  /**
   * QueryFactory constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $storage_client_manager
   *   Plugin manager instance.
   * @param \Drupal\virtual_entities\VirtualEntityDecoderServiceInterface $decoder
   *   Decoder instance.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   GuzzleHttp client.
   */
  public function __construct(PluginManagerInterface $storage_client_manager, VirtualEntityDecoderServiceInterface $decoder, ClientInterface $http_client) {
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->storageClientManager = $storage_client_manager;
    $this->decoder = $decoder;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    $class = QueryBase::getClass($this->namespaces, 'Query');

    return new $class($entity_type, $conjunction, $this->storageClientManager, $this->decoder, $this->httpClient, $this->namespaces);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    // TODO: Implement getAggregate() method.
  }

}
