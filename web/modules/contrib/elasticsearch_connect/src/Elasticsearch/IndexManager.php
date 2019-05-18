<?php

namespace Drupal\elasticsearch_connect\Elasticsearch;


use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides tools to update the Elasticsearch index
 */
class IndexManager implements IndexManagerInterface {
  
  /**
   * Elasticsearch Client Manager
   * 
   * @var \Drupal\elasticsearch_connect\Elasticsearch\ClientManagerInterface
   */
  protected $clientManager;
  
  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
 
  public function __construct(ClientManagerInterface $client_manager, ModuleHandlerInterface $module_handler) {
    $this->clientManager = $client_manager;
    $this->moduleHandler = $module_handler;
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\elasticsearch_connect\Elasticsearch\IndexManagerInterface::updateEntityIndex()
   */
  public function updateEntityIndex(EntityInterface $entity, $op) {
    
    try {
      // Allow other modules to alter the index.
      $context = [
          'entity' => $entity,
          'op' => $op,
      ];
      $data = [];
      
      // Allow other modules to alter the index.
      $this->moduleHandler->alter('elasticsearch_connect_index', $data, $context);
      
      // Index entity if enabled on entity type
      if($data) {
        
        // Check for ES client availability
        $client = $this->clientManager->getClient();
        
        if($client->ping()) {
          $config = \Drupal::config('elasticsearch_connect.settings');
  
          $params = [
              'index' => $config->get('index_id'),
              'type' => $entity->bundle(),
              'id' => $entity->id(),
              'body' => $data,
          ];
          
          // Index entity
          $client->index($params);
        } else {
          drupal_set_message(t('Error while trying to access Elasticsearch cluster. The content has not been indexed.'), 'warning');
          return NULL;
        }    
      }
      
    } catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return NULL;
    }
    
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\elasticsearch_connect\Elasticsearch\IndexManagerInterface::deleteEntityIndex()
   */
  public function deleteEntityIndex(EntityInterface $entity) {
    try {
      // Allow other modules to alter the index.
      $context = [
          'entity' => $entity,
          'op' => 'delete',
      ];
      $data = [];
      
      // Allow other modules to alter the index.
      $this->moduleHandler->alter('elasticsearch_connect_index', $data, $context);
      
      // Delete entity index if enabled on entity type
      if($data) {
        // Check for ES client availability
        $client = $this->clientManager->getClient();
        
        if($client->ping()) {
          $config = \Drupal::config('elasticsearch_connect.settings');
          
          $params = [
              'index' => $config->get('index_id'),
              'type' => $entity->bundle(),
              'id' => $entity->id(),
          ];
          
          // Delete entity from index
          $client->delete($params);
        } else {
          drupal_set_message(t('Error while trying to access Elasticsearch cluster. The content has not been deleted from index.'), 'warning');
          return NULL;
        }
      }
    } catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return NULL;
    }
  }
}