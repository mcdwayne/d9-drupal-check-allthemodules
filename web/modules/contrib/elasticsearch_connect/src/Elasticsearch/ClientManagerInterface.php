<?php
namespace Drupal\elasticsearch_connect\Elasticsearch;

interface ClientManagerInterface {
  
  /**
   * Returns an Elasticsearch client
   * 
   * @return \Elasticsearch\Client
   */
  public function getClient();
  
}