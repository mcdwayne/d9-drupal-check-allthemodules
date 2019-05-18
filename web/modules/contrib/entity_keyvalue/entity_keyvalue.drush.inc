<?php

use Drupal\entity_keyvalue\EntityKeyValueStoreProvider;

/**
 * Implements hook_drush_command().
 */
function entity_keyvalue_drush_command() {
  $commands['entity-keyvalue-set'] = [
    'description' => 'Set value for the entity',
    'arguments' => [
      'entity_type' => 'Entity type',
      'entity_id' => 'Entity identifier',
      'key' => 'Key to set its value',
      'value' => 'Value to be set',
    ],
    'examples' => [
      'drush ekv-set node 1 title "new title"' => 'Set "title" key for node 1'
    ],
    'aliases' => [
      'ekv-set'
    ]
  ];
  $commands['entity-keyvalue-get'] = [
    'description' => 'Get value by key for the entity',
    'arguments' => [
      'entity_type' => 'Entity type',
      'entity_id' => 'Entity identifier',
      'key' => 'Key to get its value',
    ],
    'examples' => [
      'drush ekv-get node 1 title' => 'Get value for "title" key for node 1'
    ],
    'aliases' => [
      'ekv-get'
    ]
  ];
  $commands['entity-keyvalue-delete'] = [
    'description' => 'Delete value by key for the entity',
    'arguments' => [
      'entity_type' => 'Entity type',
      'entity_id' => 'Entity identifier',
      'key' => 'Key to delete its value',
    ],
    'examples' => [
      'drush ekv-del node 1 title' => 'Delete value for "title" key for node 1'
    ],
    'aliases' => [
      'ekv-del'
    ]
  ];
  $commands['entity-keyvalue-get-all'] = [
    'description' => 'Get all values for the entity',
    'arguments' => [
      'entity_type' => 'Entity type',
      'entity_id' => 'Entity identifier',
    ],
    'examples' => [
      'drush ekv-get-all node 1' => 'Get all keys and its values for node 1'
    ],
    'aliases' => [
      'ekv-get-all'
    ]
  ];
  $commands['entity-keyvalue-delete-all'] = [
    'description' => 'Delete all values for the entity',
    'arguments' => [
      'entity_type' => 'Entity type',
      'entity_id' => 'Entity identifier',
    ],
    'examples' => [
      'drush ekv-del-all node 1' => 'Delete all keys values for node 1'
    ],
    'aliases' => [
      'ekv-del-all'
    ]
  ];

  return $commands;
}

/**
 * Loads entity by entity type and entity id.
 *
 * @param string $entity_type
 *   Entity type.
 * @param integer $entity_id
 *   Entity identifier.
 *
 * @return \Drupal\Core\Entity\EntityInterface
 *   Entity object.
 *
 * @throws InvalidArgumentException
 *   If entity type is incorrect or entity does not exist.
 */
function entity_keyvalue_load_entity($entity_type, $entity_id) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_definition = $entity_type_manager->getDefinition($entity_type);
  if ($entity_definition === NULL) {
    throw new InvalidArgumentException(
      dt('Entity type "@type" does not exist', ['@type' => $entity_type])
    );
  }
  $entity = $entity_type_manager->getStorage($entity_type)->load($entity_id);
  if ($entity === NULL) {
    throw new InvalidArgumentException(
      dt('Entity "@type-@id" does not exist', ['@type' => $entity_type, '@id' => $entity_id])
    );
  }

  return $entity;
}

/**
 * Callback for drush command: "entity-keyvalue-set".
 *
 * {@inheritdoc}
 */
function drush_entity_keyvalue_set($entity_type, $entity_id, $key, $value) {
  try {
    $entity = entity_keyvalue_load_entity($entity_type, $entity_id);
    /** @var EntityKeyValueStoreProvider $keyvalue_store_provider */
    $keyvalue_store_provider = \Drupal::service('entity_keyvalue_store_provider');
    $keyvalue_store_provider->getEntityStore($entity_type)
      ->setValue($entity, $key, $value);
    drush_print(dt('Value "@value" was successfully set for key @key (entity @type-@id)', [
      '@value' => $value,
      '@key' => $key,
      '@type' => $entity_type,
      '@id' => $entity_id,
    ]));
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }
}

/**
 * Callback for drush command: "entity-keyvalue-get".
 *
 * {@inheritdoc}
 */
function drush_entity_keyvalue_get($entity_type, $entity_id, $key) {
    try {
    $entity = entity_keyvalue_load_entity($entity_type, $entity_id);
    /** @var EntityKeyValueStoreProvider $keyvalue_store_provider */
    $keyvalue_store_provider = \Drupal::service('entity_keyvalue_store_provider');
    drush_print(dt('Entity (@type-@id) has value for key "@key":', [
      '@type' => $entity_type,
      '@id' => $entity_id,
      '@key' => $key,
    ]));
    drush_print_r(
      $keyvalue_store_provider
        ->getEntityStore($entity_type)
        ->loadValue($entity, $key)
    );
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }
}

/**
 * Callback for drush command: "entity-keyvalue-delete".
 *
 * {@inheritdoc}
 */
function drush_entity_keyvalue_delete($entity_type, $entity_id, $key) {
    try {
    $entity = entity_keyvalue_load_entity($entity_type, $entity_id);
    /** @var EntityKeyValueStoreProvider $keyvalue_store_provider */
    $keyvalue_store_provider = \Drupal::service('entity_keyvalue_store_provider');
    $keyvalue_store_provider->getEntityStore($entity_type)
      ->deleteValue($entity, $key);
    drush_print(dt('Value was deleted for key "@key" (entity "@type-@id)"', [
      '@key' => $key,
      '@type' => $entity_type,
      '@id' => $entity_id,
    ]));
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }
}

/**
 * Callback for drush command: "entity-keyvalue-get-all".
 *
 * {@inheritdoc}
 */
function drush_entity_keyvalue_get_all($entity_type, $entity_id) {
  try {
    $entity = entity_keyvalue_load_entity($entity_type, $entity_id);
    /** @var EntityKeyValueStoreProvider $keyvalue_store_provider */
    $keyvalue_store_provider = \Drupal::service('entity_keyvalue_store_provider');
    drush_print(dt('Entity (@type-@id) values:', [
      '@type' => $entity_type,
      '@id' => $entity_id,
    ]));
    drush_print_r(
      $keyvalue_store_provider
        ->getEntityStore($entity_type)
        ->loadValues($entity)
    );
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }
}

/**
 * Callback for drush command: "entity-keyvalue-delete-all".
 *
 * {@inheritdoc}
 */
function drush_entity_keyvalue_delete_all($entity_type, $entity_id) {
  try {
    $entity = entity_keyvalue_load_entity($entity_type, $entity_id);
    /** @var EntityKeyValueStoreProvider $keyvalue_store_provider */
    $keyvalue_store_provider = \Drupal::service('entity_keyvalue_store_provider');
    $keyvalue_store_provider->getEntityStore($entity_type)->deleteValues($entity);
    drush_print(dt('All values was deleted (entity "@type-@id)"', [
      '@type' => $entity_type,
      '@id' => $entity_id,
    ]));
  }
  catch (Exception $e) {
    return drush_set_error($e->getMessage());
  }
}
