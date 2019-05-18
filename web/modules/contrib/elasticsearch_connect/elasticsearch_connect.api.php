<?php

/**
 * @file
 * Documentation for Elasticsearch connect API.
 */

/**
 * Alter the Elasticsearch indexed data for an entity.
 *
 * @param array $data
 *   Contains the data to put in the index for the specific entity.
 * @param array $context
 *   An associative array of additional options, with the following elements:
 *   - 'entity': The entity being indexed.
 *   - 'op': A string with the operation being performed on the object being
 *     indexed. Can be either 'insert', 'update'.
 */
function hook_elasticsearch_connect_index_alter(&$data, array $context) {
  /* @var $entity \Drupal\Core\Entity\EntityInterface */
  $entity = $context['entity'];
  
  if ($entity->getEntityType()->getProvider() == 'node'
      && $entity->bundle()== 'article'
      && $context['op'] == 'insert') {
    // Add entity label to index
    $data = [
        'label' => $entity->label(),
    ];
  }
}

/**
 * Alter the Elasticsearch mapping for the specific entity.
 *
 * @param array $data
 *   Contains the mapping structure to add to the index for the specific entity.
 */
function hook_elasticsearch_connect_map_alter(&$data) {
  $data = [
      'article' => [
          'properties' => [
              'label' => [
                  'type' => 'text',
              ],
          ],
      ],
  ];
}

/**
 * For modules that create new entity types this hook allows to declare new types
 * to be indexed.
 * 
 * @param array $entity_types
 *   Array of entity types. By default only nodes are supported.
 */
function hook_elasticsearch_connect_entity_types_alter(array &$entity_types) {
  $entity_types[] = 'user';
}
