<?php

use Drupal\Component\Utility\Timer;
use Drush\Log\LogLevel;

/**
 * @file
 * Defines Elasticsearch connect drush commands.
 */

/**
 * Implements hook_drush_help().
 */
function elasticsearch_connect_drush_help($section) {
  switch ($section) {
      case 'meta:elasticsearch_connect:title':
        return dt("Elasticsearch connect commands");
      case 'meta:elasticsearch_connect:summary':
          return dt("Provides helpful tools for elasticsearch cluster.");
  }
}

/**
 * Implements hook_drush_command().
 */
function elasticsearch_connect_drush_command() {
  $items = [];
  $items['esc-create'] = [
      'description' => 'Create an Elasticsearch index.',
      'arguments' => [
          'index-id' => 'Unique, machine-readable identifier for this Index.',
      ],
      'required-arguments' => TRUE,
      'options' => [
          'num-of-shards' => [
              'description' => 'The number of shards for the index. Default to 5.',
              'example-value' => '5',
          ],
          'num-of-replicas' => [
              'description' => 'The number of shards replicas. Default to 1.',
              'example-value' => '2',
          ],
      ],
      'drupal dependencies' => ['elasticsearch_connect'],
      'aliases' => ['escc'],
      'examples' => [
          'drush esc-create my_unique_index_id' => 'Create an index named "my-unique-index-id" in the Elasticsearch cluster.',
          'drush esc-create my_unique_index_id --num-of-shards=5 --num-of-replicas=2' => 'Create an index named "my-unique-index-id" in the Elasticsearch cluster with options.',
      ],
  ];

  $items['esc-delete'] = [
      'description' => 'Delete an Elasticsearch index.',
      'arguments' => [
          'index-id' => 'Unique, machine-readable identifier for this Index.',
      ],
      'required-arguments' => TRUE,
      'drupal dependencies' => ['elasticsearch_connect'],
      'aliases' => ['escd'],
      'examples' => [
          'drush esc-delete my_unique_index_id' => 'Delete an index named "my-unique-index-id" in the Elasticsearch cluster.',
      ],
  ];
  
  $items['esc-index'] = [
      'description' => 'Index all enabled entities.',
      'drupal dependencies' => ['elasticsearch_connect'],
      'aliases' => ['esci'],
      'examples' => [
          'drush esc-index' => 'Index all enabled entities into configured index_id (see settings.php for details).',
      ],
  ];
  
  return $items;
}

/**
 * Creates Elasticsearch index callback function.
 * 
 * @see hook_elasticsearch_connect_map_alter() for more details about 
 * mapping your entity types
 * 
 * Implements drush_hook_COMMAND().
 */
function drush_elasticsearch_connect_esc_create($index_id) {
  
  try {
    // Set index params
    $num_shards = drush_get_option('num-of-shards', 5);
    $num_replicas = drush_get_option('num-of-replicas', 1);
    
    // Log some info about the index about to be created
    drush_print('Index to create ' . $index_id. '.');
    drush_print('Number of shards ' . $num_shards. '.');
    drush_print('Number of replicas ' . $num_replicas. '.');
    
    // Load Elasticsearch client
    /* @var $client_manager \Drupal\elasticsearch_connect\Elasticsearch\ClientManager */
    $client_manager = \Drupal::service('elasticsearch_connect.client_manager');
    $client = $client_manager->getClient();
    
    // Set default index params
    $params = [
        'index' => $index_id,
        'body' => [
            'settings' => [
                'number_of_shards' => $num_shards,
                'number_of_replicas' => $num_replicas,
            ],
        ],
    ];
    
    // Put mappings
    $module_handler = \Drupal::service('module_handler');
    $data = [];
    // Call modules implementing hook_elasticsearch_connect_index_alter()
    $module_handler->alter('elasticsearch_connect_map', $data);
    
    if($data) {
      $params['body']['mappings'] = $data;
    }
    
    // Create index
    $client->indices()->create($params);

    drush_log(dt('Index !index_id created successfully', ['!index_id' => $index_id]),LogLevel::SUCCESS);
    
  } catch (Exception $e) {
    drush_print($e->getMessage());
    drush_set_error(dt('Fail to create the index !index_id', ['!index_id' => $index_id]));
  }  
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_elasticsearch_connect_esc_create_validate($index_id) {
  if (!preg_match('/^[a-z][a-z0-9_]*$/i', $index_id)) {
    return drush_set_error(dt('Enter an index name that begins with a letter and contains only letters, numbers, and underscores.'));
  }
  if (drush_get_option('num-of-shards')
      && (!is_numeric(drush_get_option('num-of-shards'))
      || drush_get_option('num-of-shards') < 1)) {
    return drush_set_error(dt('Invalid number of shards.'));
  }
  if (drush_get_option('num-of-replicas')
      && (!is_numeric(drush_get_option('num-of-replicas'))
      || drush_get_option('num-of-replicas') < 1)) {
    return drush_set_error(dt('Invalid number of replicas.'));
  }
  
  /* @var $client_manager \Drupal\elasticsearch_connect\Elasticsearch\ClientManager */
  $client_manager = \Drupal::service('elasticsearch_connect.client_manager');
  $client = $client_manager->getClient();
  
  if(!$client->ping()){
    return drush_set_error(dt('Can\'t access Elasticsearch cluster. Check your elasticserach connect settings.'));
  }
}

/**
 * Deletes Elasticsearch index callback function.
 * 
 * Implements drush_hook_COMMAND().
 */
function drush_elasticsearch_connect_esc_delete($index_id) {
  
  try {

    if (drush_confirm(dt('Delete index !index_id?: ', ['!index_id' => $index_id]))) {
      /* @var $client_manager \Drupal\elasticsearch_connect\Elasticsearch\ClientManager */
      $client_manager = \Drupal::service('elasticsearch_connect.client_manager');
      $client = $client_manager->getClient();
      
      $params = [
          'index' => $index_id,
      ];
      
      // Delete index
      $client->indices()->delete($params);
      drush_log(dt('Index !index_id deleted successfully', ['!index_id' => $index_id]),LogLevel::SUCCESS);
    }
    
    
  } catch (Exception $e) {
    drush_print($e->getMessage());
    drush_set_error(dt('Fail to delete the index !index_id', ['!index_id' => $index_id]));
  }
}


/**
 * Index all enabled entities into Elasticsearch index callback function.
 *
 * Implements drush_hook_COMMAND().
 */
function drush_elasticsearch_connect_esc_index() {
  
  try {
    // Run the batch process.
    Timer::start('elasticsearch_connect_index');
    
    if (drush_confirm(dt('Do you really want to index your contents?: '))) {

      $config = \Drupal::config('elasticsearch_connect.settings');
      if($index_id = $config->get('index_id')) {
        /* @var $client_manager \Drupal\elasticsearch_connect\Elasticsearch\ClientManager */
        $client_manager = \Drupal::service('elasticsearch_connect.client_manager');
        $client = $client_manager->getClient();
        
        /** 
         * Index contents. By default only nodes are checked. In order to index 
         * other entity types please @see hook_elasticsearch_connect_entity_types_alter()
         */
        $entity_types = ['node'];
        
        // Allow other modules to alter the index.
        $module_handler = \Drupal::service('module_handler');
        $module_handler->alter('elasticsearch_connect_entity_types', $entity_types);
        
        foreach ($entity_types as $entity_type) {
          $query = \Drupal::entityQuery($entity_type);
          $entity_ids = $query->execute();
          
          $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
          $entities = $entity_storage->loadMultiple($entity_ids);
          
          if (!empty($entities)) {
            // Bulk index
            $i = 0;
            $params = ['body' => []];
            
            foreach ($entities as $entity) {
              
              $data = [];
              $context = [
                  'entity' => $entity,
                  'op' => 'insert',
              ];
              
              // Call modules implementing hook_elasticsearch_connect_index_alter()
              $module_handler->alter('elasticsearch_connect_index', $data, $context);
              
              if($data) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $index_id,
                        '_type' => $entity->bundle(),
                        '_id' => $entity->id(),
                    ]
                ];
                
                $params['body'][] = $data;
                $i++;
              }
              
              // Every 1000 stop and send the bulk request
              if($i > 0 && $i % 1000 == 0){
                $responses = $client->bulk($params);
                
                // erase the old bulk request
                $params = ['body' => []];
                
                // unset the bulk response when you are done to save memory
                unset($responses);
              }
              
            }
            
            // Send the last batch if it exists
            if (!empty($params['body'])) {
              $responses = $client->bulk($params);
            }
            
            $vars = [
                '!counter' => $i,
                '!entity_type' => $entity_type,
            ];
            drush_log(dt('!counter !entity_type entities indexed.', $vars), LogLevel::SUCCESS);
          }
        }

        
        $vars = array(
            '@timer' => Timer::read('elasticsearch_connect_index'),
            '@memory-peak' => format_size(memory_get_peak_usage(TRUE)),
        );
        drush_print(dt('Contents indexed in @timer ms. Peak memory usage: @memory-peak.', $vars));
        
        drush_log(dt('Index !index_id indexed successfully', ['!index_id' => $index_id]),LogLevel::SUCCESS);
      } else {
        drush_set_error(dt('No index_id configured in settings.php'));
      } 
    }
    
  } catch (Exception $e) {
    drush_print($e->getMessage());
    drush_set_error(dt('Fail to index contents'));
  } finally {
    Timer::stop('elasticsearch_connect_index');
  }
}
