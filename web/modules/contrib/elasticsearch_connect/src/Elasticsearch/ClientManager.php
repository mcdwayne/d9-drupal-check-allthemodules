<?php

namespace Drupal\elasticsearch_connect\Elasticsearch;

use Elasticsearch\ClientBuilder;

/**
 * Provides access to Elasticsearch cluster/index
 */
class ClientManager implements ClientManagerInterface {
  
  /**
   * @var \Elasticsearch\ClientBuilder
   */
  protected $clientBuilder;
 
  public function __construct(ClientBuilder $client_builder) {
    $this->clientBuilder = $client_builder;
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\elasticsearch_connect\Elasticsearch\ClientManagerInterface::getClient()
   */
  public function getClient() {
    $config = \Drupal::config('elasticsearch_connect.settings');

    $hosts = [
        [
            'host' => $config->get('host'),
            'port' => $config->get('port'),
            'scheme' => $config->get('scheme'),
            'user' => $config->get('user'),
            'pass' => $config->get('pass')
        ],
    ];
    
    /* @var $client \Elasticsearch\Client */
    $client = $this->clientBuilder::create()->setHosts($hosts)->build();
    
    return $client;
  }
}