<?php
use Drupal\node\NodeInterface;

/**
 * Provides configuration for entity key-value storage.
 *
 * @return array
 *   Config: [entity type => settings].
 */
function hook_entity_keyvalue_info() {
  return [
    'node' => [
      'service' => 'entity_keyvalue_store_default',
      'autoload' => FALSE,
      'autodelete' => TRUE,
      'keys' => [
        'special_counter' => [
          'default_value' => 0,
          // Default values: 'any', 'int', 'float', 'string', 'array', 'object'.
          'type' => 'int',
        ],
        'key2' => [
          // Default value can be callable.
          'default_value' => function (NodeInterface $node) {
            return $node->getTitle();
          },
          'type' => 'string',
        ],
      ],
    ],
  ];
}
